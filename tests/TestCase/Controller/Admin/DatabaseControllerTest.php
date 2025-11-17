<?php

namespace Setup\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Setup\TestSuite\DriverSkipTrait;
use Shim\TestSuite\TestCase;

/**
 * @uses \Setup\Controller\Admin\DatabaseController
 */
class DatabaseControllerTest extends TestCase {

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
	public function testForeignKeys() {
		$this->skipIfNotDriver('Mysql', 'Only for MYSQL for now');

		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Database', 'action' => 'foreignKeys']);

		$this->assertResponseCode(200);
	}

}
