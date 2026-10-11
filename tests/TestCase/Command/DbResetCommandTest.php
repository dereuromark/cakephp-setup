<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * UserUpdate command test
 *
 * @uses \Setup\Command\DbResetCommand
 */
class DbResetCommandTest extends TestCase {

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
	public function testReset() {
		$this->exec('db reset --dry-run');

		$this->assertOutputContains('DRY-RUN');
	}

	public function testDryRunPreservesUnifiedMigrationTracking(): void {
		$connection = ConnectionManager::get('test');
		$connection->execute('CREATE TABLE cake_migrations (version VARCHAR(20))');
		try {
			$this->exec('db reset --dry-run -v');
			$this->assertExitSuccess();
			$this->assertStringNotContainsString('TRUNCATE TABLE cake_migrations', $this->_out->output());
			$this->assertStringNotContainsString('TRUNCATE TABLE "cake_migrations"', $this->_out->output());
			$this->assertStringNotContainsString('TRUNCATE TABLE `cake_migrations`', $this->_out->output());
		} finally {
			$connection->execute('DROP TABLE cake_migrations');
		}
	}

}
