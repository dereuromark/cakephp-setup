<?php

namespace Setup\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
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
	public function setUp(): void {
		parent::setUp();

		$this->Controller = new Controller();
		$this->Controller->loadComponent('Flash');
		$this->Controller->Flash->Controller = $this->Controller;
		$this->Setup = new SetupComponent(new ComponentRegistry($this->Controller));
	}

	/**
	 * @return void
	 */
	public function testSetMaintenance() {
		$request = (new ServerRequest(['url' => '/?maintenance=1']))
			->withParam('action', 'index');
		$this->Controller->setRequest($request);

		$event = new Event('Controller.startup', $this->Controller, compact('request'));

		$this->Setup->beforeFilter($event);

		$result = $this->Controller->getRequest()->getSession()->read('Flash.flash');
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
			'Location' => '/',
		];
		if (version_compare(Configure::version(), '3.4.0') < 0) {
			$expected = ['Location' => '/'];
		}

		$this->assertSame($expected, $result);

		// Deactivate
		$request = (new ServerRequest(['url' => '/?maintenance=0']))
			->withParam('action', 'index');
		$this->Controller = new Controller($request);
		$this->Controller->loadComponent('Flash');
		$this->Controller->Flash->Controller = $this->Controller;
		$this->Setup = new SetupComponent(new ComponentRegistry($this->Controller));

		$event = new Event('Controller.startup', $this->Controller, compact('request'));

		$this->Setup->beforeFilter($event);

		$result = $this->Controller->getRequest()->getSession()->read('Flash.flash');
		$expected = [
			[
				'message' => __d('setup', 'Maintenance mode {0}', __d('setup', 'deactivated')),
				'key' => 'flash',
				'element' => 'Flash/success',
				'params' => [],
			],
		];
		$this->assertSame($expected, $result);

		$result = $this->Controller->getResponse()->getHeaders();
		$expected = [
			'Content-Type' => 'text/html; charset=UTF-8',
			'Location' => '/',
		];
		if (version_compare(Configure::version(), '3.4.0') < 0) {
			$expected = ['Location' => '/'];
		}
		$this->assertSame($expected, $result);
	}

}
