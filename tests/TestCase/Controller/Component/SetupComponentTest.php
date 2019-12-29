<?php

namespace Setup\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Setup\Controller\Component\SetupComponent;
use Tools\TestSuite\TestCase;

class SetupComponentTest extends TestCase {

	/**
	 * @var \App\Controller\AppController
	 */
	public $Controller;

	/**
	 * @var \Setup\Controller\Component\SetupComponent
	 */
	protected $Setup;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Controller = new Controller();
		$this->Controller->loadComponent('Flash');
		$this->Setup = new SetupComponent(new ComponentRegistry($this->Controller));
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

		$result = $this->Controller->request->getSession()->read('Flash.flash');
		$expected = [
			[
				'message' => __d('setup', 'Maintenance mode {0}', __d('setup', 'activated')),
				'key' => 'flash',
				'element' => 'Flash/success',
				'params' => [],
			],
		];
		$this->assertSame($expected, $result);

		$result = $this->Controller->response->header();
		$expected = [
			'Content-Type' => 'text/html; charset=UTF-8',
			'Location' => '/my-controller/my-action',
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

		$result = $this->Controller->request->getSession()->read('Flash.flash');
		$expected = [
			[
				'message' => __d('setup', 'Maintenance mode {0}', __d('setup', 'deactivated')),
				'key' => 'flash',
				'element' => 'Flash/success',
				'params' => [],
			],
		];
		$this->assertSame($expected, $result);

		$result = $this->Controller->response->header();
		$expected = [
			'Content-Type' => 'text/html; charset=UTF-8',
			'Location' => '/my-controller/my-action',
		];
		$this->assertSame($expected, $result);
	}

}
