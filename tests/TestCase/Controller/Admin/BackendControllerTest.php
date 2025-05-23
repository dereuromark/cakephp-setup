<?php

namespace Setup\Test\TestCase\Controller\Admin;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Setup\TestSuite\DriverSkipTrait;
use Shim\TestSuite\TestCase;
use Templating\View\Icon\BootstrapIcon;

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
	public function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Setup']);
	}

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
	public function testCookies() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'cookies']);

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
		$this->skipIfNotDriver('Mysql', 'Only for Sqlite for now');

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
		Configure::write('Icon', [
			'sets' => [
				'bs' => BootstrapIcon::class,
			],
		]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'env']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testIp() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'ip']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testTypeMap() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'typeMap']);

		$this->assertResponseCode(200);
	}

}
