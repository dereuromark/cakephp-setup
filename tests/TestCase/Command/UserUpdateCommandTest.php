<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * UserUpdate command test
 *
 * @uses \Setup\Command\UserUpdateCommand
 */
class UserUpdateCommandTest extends TestCase {

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
	public function testUpdate() {
		$this->exec('user create admin 123', ['y', 'some@email.de', 'y']);

		$this->exec('user update admin 123456', []);
		$this->assertOutputContains('Password updated for user admin');
	}

}
