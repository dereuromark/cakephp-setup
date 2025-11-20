<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Environment\MaxExecutionTimeCheck;
use Shim\TestSuite\TestCase;

class MaxExecutionTimeCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new MaxExecutionTimeCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testScope(): void {
		$check = new MaxExecutionTimeCheck();
		$this->assertSame(['web'], $check->scope());
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new MaxExecutionTimeCheck();
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
		$check = new MaxExecutionTimeCheck(60, 180);
		$check->check();

		$this->assertIsBool($check->passed());
		$this->assertNotEmpty($check->infoMessage());
	}

}
