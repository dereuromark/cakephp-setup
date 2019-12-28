<?php

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Setup\TestSuite\DriverSkipTrait;
use Tools\TestSuite\TestCase;

class SetupControllerTest extends TestCase {

	use DriverSkipTrait;
	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'admin', 'plugin' => 'Setup', 'controller' => 'Setup', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

}