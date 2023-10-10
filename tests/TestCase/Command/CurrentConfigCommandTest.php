<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * CurrentConfig command test
 */
class CurrentConfigCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testConfigure() {
		$this->exec('current_config configure');
		$this->assertOutputContains('[App]');
		$this->assertOutputContains('[namespace] =>');
	}

	/**
	 * @return void
	 */
	public function testDisplay() {
		$this->exec('current_config display');
		$this->assertOutputContains('Full Base URL:');
	}

	/**
	 * @return void
	 */
	public function testPhpinfo() {
		$this->exec('current_config phpinfo');
		$this->assertOutputContains('session.auto_start');
	}

	/**
	 * @return void
	 */
	public function testValidate() {
		$this->exec('current_config validate');
		$this->assertOutputContains('[driver]');
		$this->assertOutputContains('[className]');
	}

}
