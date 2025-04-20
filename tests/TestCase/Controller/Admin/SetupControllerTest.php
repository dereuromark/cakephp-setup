<?php

namespace Setup\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Setup\TestSuite\DriverSkipTrait;
use Shim\TestSuite\TestCase;

class SetupControllerTest extends TestCase {

	use DriverSkipTrait;
	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Setup']);
	}

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Setup', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testMaintenance() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Setup', 'action' => 'maintenance']);

		$this->assertResponseCode(200);
	}

}
