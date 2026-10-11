<?php

namespace Setup\Test\TestCase\Controller\Component;

use App\Controller\AppController;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use ReflectionMethod;
use Setup\Controller\Component\SetupComponent;
use Shim\TestSuite\TestCase;

class SetupComponentTest extends TestCase {

	/**
	 * @var \App\Controller\AppController
	 */
	public AppController|Controller $Controller;

	/**
	 * @var \Setup\Controller\Component\SetupComponent
	 */
	protected SetupComponent $Setup;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Controller = new Controller(new ServerRequest());
		$this->Controller->loadComponent('Flash');
		$this->Setup = new SetupComponent(new ComponentRegistry($this->Controller));

		$builder = Router::createRouteBuilder('/');
		$builder->setRouteClass(DashedRoute::class);
		$builder->scope('/', function (RouteBuilder $routes): void {
			$routes->fallbacks();
		});
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		Configure::delete('Setup.sessionKey');
	}

	/**
	 * @return void
	 */
	public function testSetMaintenanceOn() {
		$request = $this->Controller->getRequest()
			->withAttribute('params', ['controller' => 'MyController', 'action' => 'myAction'])
			->withQueryParams(['maintenance' => 1]);
		$this->Controller->setRequest($request);

		$event = new Event('Controller.startup', $this->Controller, ['request' => $request]);

		$this->Setup->beforeFilter($event);

		$result = $this->Controller->getRequest()->getSession()->read('Flash.flash');
		$expected = [
			[
				'message' => __d('setup', 'Maintenance mode {0}', __d('setup', 'activated')),
				'key' => 'flash',
				'element' => 'flash/success',
				'params' => [],
			],
		];
		$this->assertSame($expected, $result);

		$result = $this->Controller->getResponse()->getHeaders();
		$expected = [
			'Content-Type' => [
				'text/html; charset=UTF-8',
			],
			'Location' => [
				'https://example.com/my-controller/my-action',
			],
		];

		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testSetMaintenanceOff() {
		$request = $this->Controller->getRequest()
			->withAttribute('params', ['controller' => 'MyController', 'action' => 'myAction'])
			->withQueryParams(['maintenance' => 0]);
		$this->Controller->setRequest($request);

		$this->Controller->loadComponent('Flash');
		$this->Setup = new SetupComponent(new ComponentRegistry($this->Controller));

		$event = new Event('Controller.startup', $this->Controller, ['request' => $request]);

		$this->Setup->beforeFilter($event);

		$result = $this->Controller->getRequest()->getSession()->read('Flash.flash');
		$expected = [
			[
				'message' => __d('setup', 'Maintenance mode {0}', __d('setup', 'deactivated')),
				'key' => 'flash',
				'element' => 'flash/success',
				'params' => [],
			],
		];
		$this->assertSame($expected, $result);

		$result = $this->Controller->getResponse()->getHeaders();
		$expected = [
			'Content-Type' => [
				'text/html; charset=UTF-8',
			],
			'Location' => [
				'https://example.com/my-controller/my-action',
			],
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testIssueMailingDefaultSessionKey(): void {
		$controller = $this->getMockBuilder(Controller::class)
			->onlyMethods(['getName', 'referer'])
			->setConstructorArgs([new ServerRequest()])
			->getMock();
		$controller->expects($this->once())->method('getName')->willReturn('CakeError');
		$controller->method('referer')->willReturn('https://example.com/previous-page');
		$controller->loadComponent('Flash');

		$session = $controller->getRequest()->getSession();
		$session->write('Auth.User.id', 123);

		$component = $this->getMockBuilder(SetupComponent::class)
			->onlyMethods(['_notification'])
			->setConstructorArgs([new ComponentRegistry($controller)])
			->getMock();
		$component->notifications = ['404' => true];
		$component->Controller = $controller;

		$component->expects($this->once())
			->method('_notification')
			->with(
				'404!',
				$this->stringContains('UID: 123'),
			)
			->willReturn(true);

		putenv('REMOTE_ADDR=192.168.1.1');
		Configure::write('debug', 0);

		$component->issueMailing();

		putenv('REMOTE_ADDR');
	}

	/**
	 * @return void
	 */
	public function testIssueMailingCustomSessionKey(): void {
		$controller = $this->getMockBuilder(Controller::class)
			->onlyMethods(['getName', 'referer'])
			->setConstructorArgs([new ServerRequest()])
			->getMock();
		$controller->expects($this->once())->method('getName')->willReturn('CakeError');
		$controller->method('referer')->willReturn('https://example.com/previous-page');
		$controller->loadComponent('Flash');

		$session = $controller->getRequest()->getSession();
		$session->write('Auth.id', 456);

		Configure::write('Setup.sessionKey', 'Auth');

		$component = $this->getMockBuilder(SetupComponent::class)
			->onlyMethods(['_notification'])
			->setConstructorArgs([new ComponentRegistry($controller)])
			->getMock();
		$component->notifications = ['404' => true];
		$component->Controller = $controller;

		$component->expects($this->once())
			->method('_notification')
			->with(
				'404!',
				$this->stringContains('UID: 456'),
			)
			->willReturn(true);

		putenv('REMOTE_ADDR=192.168.1.1');
		Configure::write('debug', 0);

		$component->issueMailing();

		putenv('REMOTE_ADDR');
	}

	public function testProductionQuickSwitchRequiresPasswordWithoutLegacyFlag(): void {
		Configure::delete('Config.productive');
		Configure::delete('Config.pwd');
		Configure::write('debug', false);
		$this->Setup->Controller = $this->Controller;
		try {
			$this->assertFalse((bool)$this->Setup->isAuthorized());
		} finally {
			Configure::write('debug', true);
		}
	}

	public function testProductionQuickSwitchAcceptsConfiguredPassword(): void {
		Configure::delete('Config.productive');
		Configure::write('Config.pwd', 'maintenance-password');
		Configure::write('debug', false);
		$this->Controller->setRequest($this->Controller->getRequest()->withQueryParams(['pwd' => 'maintenance-password']));
		$this->Setup->Controller = $this->Controller;
		try {
			$this->assertTrue((bool)$this->Setup->isAuthorized());
		} finally {
			Configure::delete('Config.pwd');
			Configure::write('debug', true);
		}
	}

	public function testSessionDebugDoesNotDisableProductionPasswordGate(): void {
		Configure::delete('Config.productive');
		Configure::write('debug', false);
		Configure::write('Config.pwd', 'secret');
		$this->Controller->getRequest()->getSession()->write('Setup.debug', true);
		try {
			$this->Setup->beforeFilter(new Event('Controller.beforeFilter', $this->Controller));
			$this->assertTrue(Configure::read('debug'));
			$this->assertFalse((bool)$this->Setup->isAuthorized());
		} finally {
			$this->Controller->getRequest()->getSession()->delete('Setup.debug');
			Configure::delete('Config.pwd');
			Configure::write('debug', true);
		}
	}

	public function testInferredProductionRedirectRemovesPassword(): void {
		Configure::delete('Config.productive');
		Configure::write('debug', false);
		$this->Controller->setRequest($this->Controller->getRequest()->withQueryParams(['pwd' => 'secret', 'debug' => '1']));
		$this->Setup->Controller = $this->Controller;
		try {
			$method = new ReflectionMethod($this->Setup, '_cleanedUrl');
			$this->assertArrayNotHasKey('pwd', $method->invoke($this->Setup, 'debug')['?'] ?? []);
		} finally {
			Configure::write('debug', true);
		}
	}

}
