<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * UserCreate command test
 */
class UserCreateCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testCreate() {
		$this->exec('user create admin 123', ['y', 'some@email.de', 'y']);
		$this->assertOutputContains('User inserted! ID: 1');
	}

}
