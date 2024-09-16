<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

class MaintenanceModeStatusCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Setup']);

		unlink(TMP . 'maintenance.txt');
	}

	/**
	 * @return void
	 */
	public function testStatus() {
		$this->exec('maintenance_mode status');
		$this->assertOutputContains('Maintenance mode not active');
	}

	/**
	 * @return void
	 */
	public function testStatusActive() {
		$this->exec('maintenance_mode activate');

		$this->exec('maintenance_mode status');
		$this->assertOutputContains('Maintenance mode active');
	}

}
