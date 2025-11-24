<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Setup\TestSuite\DriverSkipTrait;

/**
 * DbDataDates command test
 *
 * @uses \Setup\Command\DbDataDatesCommand
 */
class DbDataDatesCommandTest extends TestCase {

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

		$this->exec('db_data dates');

		$this->assertExitSuccess();
	}

	/**
	 * @return void
	 */
	public function testExecuteWithTable(): void {
		$this->skipIfNotDriver('Mysql');

		$this->exec('db_data dates users');

		$this->assertExitSuccess();
	}

	/**
	 * @return void
	 */
	public function testExecuteVerbose(): void {
		$this->skipIfNotDriver('Mysql');

		$this->exec('db_data dates -v');

		$this->assertExitSuccess();
	}

	/**
	 * @return void
	 */
	public function testHelp(): void {
		$this->exec('db_data dates --help');

		$this->assertExitSuccess();
		$this->assertOutputContains('invalid zero date');
	}

}
