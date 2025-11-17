<?php

namespace Setup\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Setup\Controller\Component\HealthcheckComponent;

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

}
