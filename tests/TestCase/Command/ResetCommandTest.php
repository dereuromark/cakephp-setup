<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * ResetCommand command test
 */
class ResetCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testUpdate() {
		$this->exec('reset pwd', ['123']);

		$this->assertOutputContains('0 pwds resetted - DONE');
	}

}
