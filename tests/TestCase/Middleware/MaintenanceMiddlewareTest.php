<?php

namespace Setup\Test\TestCase\Middleware;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Psr\Http\Server\RequestHandlerInterface;
use Setup\Maintenance\Maintenance;
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

	/**
	 * @return void
	 */
	public function testIsPathWhitelistedExactMatch(): void {
		$middleware = new MaintenanceMiddleware([
			'pathWhitelist' => ['/health', '/setup/healthcheck'],
		]);

		$this->assertTrue($this->invokeMethod($middleware, 'isPathWhitelisted', ['/health']));
		$this->assertTrue($this->invokeMethod($middleware, 'isPathWhitelisted', ['/setup/healthcheck']));
		$this->assertFalse($this->invokeMethod($middleware, 'isPathWhitelisted', ['/other']));
		$this->assertFalse($this->invokeMethod($middleware, 'isPathWhitelisted', ['/']));
	}

	/**
	 * @return void
	 */
	public function testIsPathWhitelistedPrefixMatch(): void {
		$middleware = new MaintenanceMiddleware([
			'pathWhitelist' => ['/api/health'],
		]);

		$this->assertTrue($this->invokeMethod($middleware, 'isPathWhitelisted', ['/api/health']));
		$this->assertTrue($this->invokeMethod($middleware, 'isPathWhitelisted', ['/api/health/detailed']));
		$this->assertFalse($this->invokeMethod($middleware, 'isPathWhitelisted', ['/api/healthcheck']));
		$this->assertFalse($this->invokeMethod($middleware, 'isPathWhitelisted', ['/api']));
	}

	/**
	 * @return void
	 */
	public function testIsPathWhitelistedEmptyList(): void {
		$middleware = new MaintenanceMiddleware();

		$this->assertFalse($this->invokeMethod($middleware, 'isPathWhitelisted', ['/health']));
		$this->assertFalse($this->invokeMethod($middleware, 'isPathWhitelisted', ['/']));
	}

	public function testMaintenanceDoesNotRunDownstreamPostHandler(): void {
		$maintenance = new Maintenance();
		$maintenance->setMaintenanceMode(true);
		$handler = $this->createMock(RequestHandlerInterface::class);
		$handler->expects($this->never())->method('handle');
		try {
			$request = new ServerRequest(['url' => '/orders', 'environment' => ['REMOTE_ADDR' => '192.0.2.50', 'REQUEST_METHOD' => 'POST']]);
			$response = (new MaintenanceMiddleware())->process($request, $handler);
			$this->assertSame(503, $response->getStatusCode());
		} finally {
			$maintenance->setMaintenanceMode(false);
		}
	}

	public function testWhitelistedPathRunsDownstreamHandlerDuringMaintenance(): void {
		$maintenance = new Maintenance();
		$maintenance->setMaintenanceMode(true);
		$handler = $this->createMock(RequestHandlerInterface::class);
		$handler->expects($this->once())->method('handle')->willReturn(new Response());
		try {
			$request = new ServerRequest(['url' => '/health', 'environment' => ['REMOTE_ADDR' => '192.0.2.50']]);
			$response = (new MaintenanceMiddleware(['pathWhitelist' => ['/health']]))->process($request, $handler);
			$this->assertSame(200, $response->getStatusCode());
		} finally {
			$maintenance->setMaintenanceMode(false);
		}
	}

}
