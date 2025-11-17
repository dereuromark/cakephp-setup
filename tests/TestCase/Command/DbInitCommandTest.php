<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Setup\TestSuite\DriverSkipTrait;

/**
 * UserUpdate command test
 *
 * @uses \Setup\Command\DbInitCommand
 */
class DbInitCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;
	use DriverSkipTrait;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->loadPlugins(['Setup']);
	}

	/**
	 * @return void
	 */
	public function testInit() {
		$this->skipIfNotDriver('Sqlite');

		$this->exec('db init');

		$this->assertErrorContains('Using in-memory database, skipping');
	}

}
