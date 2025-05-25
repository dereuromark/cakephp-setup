<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Core;

use Setup\Healthcheck\Check\Core\CakeVersionCheck;
use Shim\TestSuite\TestCase;

class CakeVersionCheckTest extends TestCase {

	protected string $testFiles;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->testFiles = ROOT . DS . 'tests' . DS . 'test_files' . DS . 'healthcheck' . DS;
	}

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new CakeVersionCheck();
		$this->assertSame('Core', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheck() {
		$check = new CakeVersionCheck('5.2.1', $this->testFiles . 'PhpVersionCheck' . DS . 'basic' . DS);

		$check->check();
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
	}

	/**
	 * @return void
	 */
	public function testCheckTooLow() {
		$check = new CakeVersionCheck('5.2.0', $this->testFiles . 'PhpVersionCheck' . DS . 'basic' . DS);

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertNotEmpty($check->failureMessage());
	}

	/**
	 * @return void
	 */
	public function testCheckTooHigh() {
		$check = new CakeVersionCheck('5.3.0', $this->testFiles . 'PhpVersionCheck' . DS . 'basic' . DS);

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertNotEmpty($check->failureMessage());
	}

}
