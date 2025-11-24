<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Setup\TestSuite\DriverSkipTrait;

/**
 * DbDataEnums command test
 *
 * @uses \Setup\Command\DbDataEnumsCommand
 */
class DbDataEnumsCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;
	use DriverSkipTrait;

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
	public function testExecute(): void {
		$this->skipIfNotDriver('Mysql');

		$this->exec('db_data enums');

		$this->assertExitSuccess();
	}

	/**
	 * @return void
	 */
	public function testExecuteWithModel(): void {
		$this->skipIfNotDriver('Mysql');

		$this->exec('db_data enums Users');

		$this->assertExitSuccess();
	}

	/**
	 * @return void
	 */
	public function testExecuteVerbose(): void {
		$this->skipIfNotDriver('Mysql');

		$this->exec('db_data enums -v');

		$this->assertExitSuccess();
	}

	/**
	 * @return void
	 */
	public function testHelp(): void {
		$this->exec('db_data enums --help');

		$this->assertExitSuccess();
		$this->assertOutputContains('enum values');
	}

}
