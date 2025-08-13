<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Environment\PhpVersionCheck;
use Shim\TestSuite\TestCase;

class PhpVersionCheckTest extends TestCase {

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
		$check = new PhpVersionCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheck() {
		$check = new PhpVersionCheck('8.3.10', $this->testFiles . 'PhpVersionCheck' . DS . 'basic' . DS);

		$check->check();
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
	}

	/**
	 * @return void
	 */
	public function testCheckTooLow() {
		$check = new PhpVersionCheck('8.3.0', $this->testFiles . 'PhpVersionCheck' . DS . 'basic' . DS);

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertNotEmpty($check->failureMessage());
	}

	/**
	 * @return void
	 */
	public function testCheckTooHigh() {
		$check = new PhpVersionCheck('8.4.0', $this->testFiles . 'PhpVersionCheck' . DS . 'basic' . DS);

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertNotEmpty($check->failureMessage());
	}

	/**
	 * @return void
	 */
	public function testCheckTooHighMinorWithMajorConfig() {
		// With failOnHigher = 'major', minor version differences should fail with warning level
		$check = new PhpVersionCheck('8.4.0', $this->testFiles . 'PhpVersionCheck' . DS . 'basic' . DS, null, 'major');

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertEmpty($check->failureMessage());
		$this->assertNotEmpty($check->warningMessage());
		$this->assertSame($check::LEVEL_WARNING, $check->level());
	}

	/**
	 * @return void
	 */
	public function testCheckTooHighMajorAlwaysFails() {
		// Major version differences should always fail regardless of config
		$check = new PhpVersionCheck('9.0.0', $this->testFiles . 'PhpVersionCheck' . DS . 'basic' . DS, null, 'major');

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertNotEmpty($check->failureMessage());
	}

	/**
	 * @return void
	 */
	public function testCheckTooLowAlwaysFails() {
		// Lower versions should always fail regardless of config
		$check = new PhpVersionCheck('8.3.0', $this->testFiles . 'PhpVersionCheck' . DS . 'basic' . DS, null, 'major');

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertNotEmpty($check->failureMessage());
	}

}
