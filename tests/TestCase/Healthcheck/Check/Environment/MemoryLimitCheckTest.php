<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Environment\MemoryLimitCheck;
use Shim\TestSuite\TestCase;

class MemoryLimitCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new MemoryLimitCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new MemoryLimitCheck();
		$check->check();

		// Test should run without errors
		$this->assertIsBool($check->passed());
		$this->assertNotEmpty($check->infoMessage());
	}

	/**
	 * @return void
	 */
	public function testCheckWithCustomLimits(): void {
		// Test with custom min and max limits
		$check = new MemoryLimitCheck(256, 2048);
		$check->check();

		$this->assertIsBool($check->passed());
		$this->assertNotEmpty($check->infoMessage());
	}

}
