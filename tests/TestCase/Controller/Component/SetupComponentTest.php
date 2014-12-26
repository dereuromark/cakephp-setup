<?php

namespace Setup\Test\TestCase\Controller\Component;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Setup\Routing\Filter\MaintenanceFilter;
use Tools\TestSuite\TestCase;
use Setup\Controller\Component\SetupComponent;
use Cake\Controller\ComponentRegistry;

use Cake\Controller\Controller;

class SetupComponentTest extends TestCase {

	public function setUp() {
		parent::setUp();

		$this->request = new Request('/?maintenance=1');
		$this->Controller = $this->getMock('Cake\Controller\Controller', ['referer'], [$this->request]);
		$this->Controller->loadComponent('Tools.Flash');
		$this->Controller->Flash->Controller = $this->Controller;
		$this->Setup = new SetupComponent(new ComponentRegistry($this->Controller));

		$this->Controller->request->params = array('action' => 'index');
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
		$request = $this->request;
		$event = new Event('Controller.startup', $this->Controller, compact('request'));

		$this->Setup->beforeFilter($event);

		$this->Controller->expects($this->never())
			->method('redirect');

		$result = $this->Controller->request->session()->read('messages');
		$expected = array('success' => array(__d('setup', 'Maintenance mode activated')));
		$this->assertSame($expected, $result);

		$result = $this->Controller->response->header();
		$expected = ['Location' => '/'];
		$this->assertSame($expected, $result);
	}

}
