<?php

namespace Setup\Test\TestCase\Controller\Component;

use App\Controller\AppController;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
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
	public function testSetMaintenanceOn() {
		$request = $this->Controller->getRequest()
			->withAttribute('params', ['controller' => 'MyController', 'action' => 'myAction'])
			->withQueryParams(['maintenance' => 1]);
		$this->Controller->setRequest($request);

		$event = new Event('Controller.startup', $this->Controller, compact('request'));

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

		$event = new Event('Controller.startup', $this->Controller, compact('request'));

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

}
