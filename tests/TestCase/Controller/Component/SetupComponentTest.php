<?php

namespace Setup\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Setup\Controller\Component\SetupComponent;
use Tools\TestSuite\TestCase;

class SetupComponentTest extends TestCase {

	public function setUp() {
		parent::setUp();

		$this->Controller = new Controller();
		$this->Controller->loadComponent('Tools.Flash');
		$this->Controller->Flash->Controller = $this->Controller;
		$this->Setup = new SetupComponent(new ComponentRegistry($this->Controller));
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * SetupComponentTest::testSetMaintenance()
	 *
	 * @return void
	 */
	public function testSetMaintenance() {
		$request = new Request('/?maintenance=1');
		$this->Controller->request = $request;
		$this->Controller->request->params = array('action' => 'index');

		$request = $request;
		$event = new Event('Controller.startup', $this->Controller, compact('request'));

		$this->Setup->beforeFilter($event);

		$result = $this->Controller->request->session()->read('FlashMessage');
		$expected = array('success' => array(__d('setup', 'Maintenance mode {0}', __d('setup', 'activated'))));
		$this->assertSame($expected, $result);

		$result = $this->Controller->response->header();
		$expected = ['Location' => '/'];
		$this->assertSame($expected, $result);

		// Deactivate
		$request = new Request('/?maintenance=0');
		$this->Controller = new Controller($request);
		$this->Controller->loadComponent('Tools.Flash');
		$this->Controller->Flash->Controller = $this->Controller;
		$this->Setup = new SetupComponent(new ComponentRegistry($this->Controller));

		$this->Controller->request->params = array('action' => 'index');

		$request = $request;
		$event = new Event('Controller.startup', $this->Controller, compact('request'));

		$this->Setup->beforeFilter($event);

		$result = $this->Controller->request->session()->read('FlashMessage');
		$expected = array('success' => array(__d('setup', 'Maintenance mode {0}', __d('setup', 'deactivated'))));
		$this->assertSame($expected, $result);

		$result = $this->Controller->response->header();
		$expected = ['Location' => '/'];
		$this->assertSame($expected, $result);
	}

}
