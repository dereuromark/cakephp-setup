<?php

namespace Setup\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use Setup\Controller\Component\SetupComponent;
use Tools\TestSuite\TestCase;

class SetupComponentTest extends TestCase {

	/**
	 * @var \App\Controller\AppController
	 */
	public $Controller;

	public function setUp() {
		parent::setUp();

		$this->Controller = new Controller();
		$this->Controller->loadComponent('Flash');
		$this->Controller->Flash->Controller = $this->Controller;
		$this->Setup = new SetupComponent(new ComponentRegistry($this->Controller));
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testSetMaintenance() {
		$request = new Request('/?maintenance=1');
		$this->Controller->request = $request;
		$this->Controller->request->params = ['action' => 'index'];

		$event = new Event('Controller.startup', $this->Controller, compact('request'));

		$this->Setup->beforeFilter($event);

		$result = $this->Controller->request->session()->read('Flash.flash');
		$expected = [
			[
				'message' => __d('setup', 'Maintenance mode {0}', __d('setup', 'activated')),
				'key' => 'flash',
				'element' => 'Flash/success',
				'params' => []
			]
		];
		$this->assertSame($expected, $result);

		$result = $this->Controller->response->header();
		$expected = [
			'Content-Type' => 'text/html; charset=UTF-8',
			'Location' => '/'
		];
		if (version_compare(Configure::version(), '3.4.0') < 0) {
			$expected = ['Location' => '/'];
		}

		$this->assertSame($expected, $result);

		// Deactivate
		$request = new Request('/?maintenance=0');
		$this->Controller = new Controller($request);
		$this->Controller->loadComponent('Flash');
		$this->Controller->Flash->Controller = $this->Controller;
		$this->Setup = new SetupComponent(new ComponentRegistry($this->Controller));

		$this->Controller->request->params = ['action' => 'index'];

		$event = new Event('Controller.startup', $this->Controller, compact('request'));

		$this->Setup->beforeFilter($event);

		$result = $this->Controller->request->session()->read('Flash.flash');
		$expected = [
			[
				'message' => __d('setup', 'Maintenance mode {0}', __d('setup', 'deactivated')),
				'key' => 'flash',
				'element' => 'Flash/success',
				'params' => []
			]
		];
		$this->assertSame($expected, $result);

		$result = $this->Controller->response->header();
		$expected = [
			'Content-Type' => 'text/html; charset=UTF-8',
			'Location' => '/'
		];
		if (version_compare(Configure::version(), '3.4.0') < 0) {
			$expected = ['Location' => '/'];
		}
		$this->assertSame($expected, $result);
	}

}
