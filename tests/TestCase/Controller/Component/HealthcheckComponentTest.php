<?php

namespace Setup\Test\TestCase\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Setup\Controller\Component\HealthcheckComponent;
use TestApp\Healthcheck\Check\CountingCheck;

class HealthcheckComponentTest extends TestCase {

	protected HealthcheckComponent $component;

	protected Controller $controller;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$request = new ServerRequest();
		$response = new Response();
		$this->controller = new Controller($request, $response);
		$registry = new ComponentRegistry($this->controller);
		$this->component = new HealthcheckComponent($registry);
	}

	/**
	 * @return void
	 */
	public function testRun(): void {
		$result = $this->component->run();

		$this->assertIsArray($result);
		$this->assertArrayHasKey('passed', $result);
		$this->assertArrayHasKey('result', $result);
		$this->assertArrayHasKey('domains', $result);
		$this->assertArrayHasKey('errors', $result);
		$this->assertArrayHasKey('warnings', $result);
		$this->assertArrayHasKey('executionTime', $result);
		$this->assertIsBool($result['passed']);
		$this->assertIsFloat($result['executionTime']);
	}

	/**
	 * @return void
	 */
	public function testRunWithDomain(): void {
		$result = $this->component->run('Core');

		$this->assertIsArray($result);
		$this->assertArrayHasKey('passed', $result);
	}

	/**
	 * @return void
	 */
	public function testHandleResponseJson(): void {
		$data = $this->component->run();

		$request = $this->controller->getRequest()->withEnv('HTTP_ACCEPT', 'application/json');
		$this->controller->setRequest($request);

		$response = $this->component->handleResponse($data, false);

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame('application/json', $response->getType());
	}

	/**
	 * @return void
	 */
	public function testHandleResponseAlwaysShowDetails(): void {
		$data = $this->component->run();

		$response = $this->component->handleResponse($data, true);

		// Should return null and set view vars instead
		$this->assertNull($response);
		$this->assertNotEmpty($this->controller->viewBuilder()->getVars());
	}

	public function testRefreshAndCachedJson(): void {
		Cache::setConfig('healthcheck_test', ['className' => 'Array']);
		Configure::write('Setup.Healthcheck', ['cache' => 'healthcheck_test', 'checks' => [CountingCheck::class]]);
		$debug = Configure::read('debug');
		CountingCheck::$runs = 0;
		try {
			Configure::write('debug', false);
			$this->controller->setRequest($this->controller->getRequest()->withQueryParams(['refresh' => '1'])->withEnv('HTTP_ACCEPT', 'application/json'));
			$this->component->run();
			$data = $this->component->run();
			$this->assertSame(1, CountingCheck::$runs);
			$response = $this->component->handleResponse($data, true);
			$json = json_decode((string)$response->getBody(), true);
			$this->assertTrue($json['result']['Test'][0]['cached']);
			$this->assertArrayHasKey('cached_at', $json['result']['Test'][0]);
			$this->component->run(null, true);
			$this->assertSame(2, CountingCheck::$runs);
			Configure::write('debug', true);
			$data = $this->component->run();
			$this->assertSame(3, CountingCheck::$runs);
			$response = $this->component->handleResponse($data);
			$json = json_decode((string)$response->getBody(), true);
			$this->assertFalse($json['result']['Test'][0]['cached']);
			$this->assertArrayNotHasKey('cached_at', $json['result']['Test'][0]);
		} finally {
			Cache::drop('healthcheck_test');
			Configure::delete('Setup.Healthcheck');
			Configure::write('debug', $debug);
		}
	}

}
