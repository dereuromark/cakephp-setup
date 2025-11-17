<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
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

}
