<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Environment\DiskSpaceCheck;
use Shim\TestSuite\TestCase;

class DiskSpaceCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new DiskSpaceCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new DiskSpaceCheck();
		$check->check();

		// Test should pass on most systems with sufficient disk space
		$this->assertIsBool($check->passed());
	}

	/**
	 * @return void
	 */
	public function testCheckWithCustomThresholds(): void {
		// Test with custom thresholds
		$check = new DiskSpaceCheck(90, 99);
		$check->check();

		$this->assertIsBool($check->passed());
	}

	/**
	 * @return void
	 */
	public function testCheckWithCustomPaths(): void {
		// Test with specific paths
		$check = new DiskSpaceCheck(80, 95, ['/tmp']);
		$check->check();

		$this->assertIsBool($check->passed());
	}

	/**
	 * @return void
	 */
	public function testCheckWithInvalidPath(): void {
		$check = new DiskSpaceCheck(80, 95, ['/nonexistent/path/that/does/not/exist']);
		$check->check();

		$this->assertFalse($check->passed());
		$this->assertNotEmpty($check->failureMessage());
	}

}
