<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * ResetCommand command test
 *
 * @uses \Setup\Command\ResetCommand
 */
class ResetCommandTest extends TestCase {

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
		$this->exec('reset pwd 123');

		$this->assertOutputContains('0 pwds reset - DONE');
	}

	/**
	 * @return void
	 */
	public function testUpdatePrompt() {
		$this->exec('reset pwd', ['123']);

		$this->assertOutputContains('0 pwds reset - DONE');
	}

}
