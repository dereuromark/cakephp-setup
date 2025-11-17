<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \Setup\Command\MaintenanceModeActivateCommand
 */
class MaintenanceModeActivateCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->loadPlugins(['Setup']);
	}

	/**
	 * @return void
	 */
	public function testActivate() {
		$this->exec('maintenance_mode activate');
		$this->assertOutputContains('Maintenance mode activated ...');
	}

}
