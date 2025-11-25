<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Environment\AllowUrlIncludeCheck;
use Shim\TestSuite\TestCase;

class AllowUrlIncludeCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new AllowUrlIncludeCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testScope(): void {
		$check = new AllowUrlIncludeCheck();
		$this->assertSame(['web', 'cli'], $check->scope());
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new AllowUrlIncludeCheck();
		$check->check();

		// On most systems, allow_url_include should be disabled by default
		$this->assertTrue($check->passed());
	}

	/**
	 * @return void
	 */
	public function testLevel(): void {
		$check = new AllowUrlIncludeCheck();
		$this->assertSame('warning', $check->level());
	}

}
