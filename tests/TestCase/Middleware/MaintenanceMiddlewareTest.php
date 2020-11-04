<?php

namespace Setup\Test\TestCase\Middleware;

use Cake\Http\Response;
use Setup\Middleware\MaintenanceMiddleware;
use Shim\TestSuite\TestCase;
use Shim\TestSuite\TestTrait;

class MaintenanceMiddlewareTest extends TestCase {

	use TestTrait;

	/**
	 * @var \Setup\Middleware\MaintenanceMiddleware
	 */
	protected $middleware;

	/**
	 * @return void
	 */
	public function testBuild() {
		$middleware = new MaintenanceMiddleware();

		$response = new Response();
		/** @var \Cake\Http\Response $result */
		$result = $this->invokeMethod($middleware, 'build', [$response]);

		$this->assertStringContainsString('Please wait... We will be back shortly', $result->getBody()->getContents());

		$this->assertSame(503, $result->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testBuildCustomLayout() {
		$config = [
			'templateLayout' => 'maintenance',
		];

		$middleware = new MaintenanceMiddleware($config);

		$response = new Response();
		/** @var \Cake\Http\Response $result */
		$result = $this->invokeMethod($middleware, 'build', [$response]);

		$body = $result->getBody()->getContents();
		$this->assertStringContainsString('Please wait... We will be back shortly', $body);
		$this->assertStringContainsString('<h1>We are working right now</h1>', $body);
	}

}
