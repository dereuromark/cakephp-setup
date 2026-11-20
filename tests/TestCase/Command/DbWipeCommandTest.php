<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * UserUpdate command test
 *
 * @uses \Setup\Command\DbWipeCommand
 */
class DbWipeCommandTest extends TestCase {

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
		$this->exec('db wipe --dry-run');

		$this->assertOutputContains('DRY-RUN');
	}

	public function testQuietWithoutForcePreservesTables(): void {
		$this->assertWipeAborts('db wipe --connection test -q');
		$this->assertErrorContains('--force');
	}

	public function testDeclinedConfirmationPreservesTables(): void {
		$this->assertWipeAborts('db wipe --connection test', ['n']);
		$this->assertErrorContains('Aborted!');
	}

	public function testEmptyConfirmationPreservesTables(): void {
		$this->assertWipeAborts('db wipe --connection test', ['']);
		$this->assertErrorContains('Aborted!');
	}

	/**
	 * @param string $command
	 * @param array<string> $input
	 */
	protected function assertWipeAborts(string $command, array $input = []): void {
		$connection = ConnectionManager::get('test');
		$connection->execute('CREATE TABLE wipe_confirmation_test (id INTEGER)');
		try {
			$this->exec($command, $input);
			$this->assertExitError();
			$this->assertContains('wipe_confirmation_test', $connection->getSchemaCollection()->listTables());
		} finally {
			$connection->execute('DROP TABLE wipe_confirmation_test');
		}
	}

}
