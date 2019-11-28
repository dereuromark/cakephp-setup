<?php

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Setup\TestSuite\DriverSkipTrait;
use Tools\TestSuite\TestCase;

class DatabaseControllerTest extends TestCase {

	use DriverSkipTrait;
	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testForeignKeys() {
		$this->skipIfNotDriver('Mysql', 'Only for MYSQL for now');

		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'admin', 'plugin' => 'Setup', 'controller' => 'Database', 'action' => 'foreignKeys']);

		$this->assertResponseCode(200);
	}

}
