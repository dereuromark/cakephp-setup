<?php

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Setup\TestSuite\DriverSkipTrait;
use Shim\TestSuite\TestCase;

class BackendControllerTest extends TestCase {

	use DriverSkipTrait;
	use IntegrationTestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Setup.Sessions',
	];

	/**
	 * @return void
	 */
	public function testPhpinfo() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'phpinfo']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testSession() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'session']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testCache() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'cache']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testDatabase() {
		$this->skipIfNotDriver('Mysql', 'Only for MYSQL for now');

		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'database']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testEnv() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'env']);

		$this->assertResponseCode(200);
	}

}
