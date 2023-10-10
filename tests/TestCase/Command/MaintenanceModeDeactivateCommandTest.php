<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

class MaintenanceModeDeactivateCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testDeactivate() {
		$this->exec('maintenance_mode deactivate');
		$this->assertOutputContains('Maintenance mode deactivated ...');
	}

}
