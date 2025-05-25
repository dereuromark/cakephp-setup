<?php

namespace Setup\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Shim\TestSuite\TestCase;

class HealthcheckControllerTest extends TestCase {

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

		$this->get(['plugin' => 'Setup', 'controller' => 'Healthcheck', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

}
