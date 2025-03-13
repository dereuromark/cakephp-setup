<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * UserUpdate command test
 */
class DbInitCommandTest extends TestCase {

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
	public function testInit() {
		$this->exec('db init');

		$this->assertErrorContains('Using in-memory database, skipping');
	}

}
