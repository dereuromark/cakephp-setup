<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

class MaintenanceModeWhitelistCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Setup']);

		$matches = glob(TMP . 'maintenanceOverride-*\.txt');
		if ($matches) {
			foreach ($matches as $match) {
				unlink($match);
			}
		}
	}

	/**
	 * @return void
	 */
	public function testWhitelist() {
		$this->exec('maintenance_mode whitelist');
		$this->assertOutputContains('n/a');

		$this->exec('maintenance_mode whitelist 192.168.0.1');
		$this->assertOutputContains('192.168.0.1');

		$this->exec('maintenance_mode whitelist -r');
		$this->assertOutputContains('n/a');
	}

}
