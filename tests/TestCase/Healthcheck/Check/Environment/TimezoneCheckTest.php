<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Environment\TimezoneCheck;
use Shim\TestSuite\TestCase;

class TimezoneCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new TimezoneCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testScope(): void {
		$check = new TimezoneCheck();
		$this->assertSame(['web', 'cli'], $check->scope());
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new TimezoneCheck();
		$check->check();

		$this->assertIsBool($check->passed());
	}

	/**
	 * @return void
	 */
	public function testLevel(): void {
		$check = new TimezoneCheck();
		$this->assertSame('warning', $check->level());
	}

}
