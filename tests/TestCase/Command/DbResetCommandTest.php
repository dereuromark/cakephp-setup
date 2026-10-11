<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Setup\Command\DbResetCommand;

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
			$this->assertStringNotContainsString('TRUNCATE TABLE cake_migrations', $this->out->output());
			$this->assertStringNotContainsString('TRUNCATE TABLE "cake_migrations"', $this->out->output());
			$this->assertStringNotContainsString('TRUNCATE TABLE `cake_migrations`', $this->out->output());
		} finally {
			$connection->execute('DROP TABLE cake_migrations');
		}
	}

	public function testEmptyDatabaseDoesNotGenerateTruncateSql(): void {
		$connection = new Connection([
			'driver' => Sqlite::class,
			'database' => ':memory:',
		]);
		$command = $this->getMockBuilder(DbResetCommand::class)
			->onlyMethods(['_getConnection'])->getMock();
		$command->expects($this->once())->method('_getConnection')->willReturn($connection);
		$output = new StubConsoleOutput();
		$io = new ConsoleIo($output);
		$this->assertSame(0, $command->run(['--connection', 'test', '--dry-run'], $io));
		$this->assertStringContainsString('No application tables', $output->output());
		$this->assertStringNotContainsString('TRUNCATE', $output->output());
	}

}
