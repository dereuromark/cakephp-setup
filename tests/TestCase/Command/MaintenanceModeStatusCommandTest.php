<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \Setup\Command\MaintenanceModeStatusCommand
 */
class MaintenanceModeStatusCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Setup']);

		if (file_exists(TMP . 'maintenance.txt')) {
			unlink(TMP . 'maintenance.txt');
		}
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
