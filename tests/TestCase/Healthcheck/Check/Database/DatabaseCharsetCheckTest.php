<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Database;

use Setup\Healthcheck\Check\Database\DatabaseCharsetCheck;
use Shim\TestSuite\TestCase;

class DatabaseCharsetCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new DatabaseCharsetCheck();
		$this->assertSame('Database', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testScope(): void {
		$check = new DatabaseCharsetCheck();
		$this->assertSame(['web', 'cli'], $check->scope());
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new DatabaseCharsetCheck();
		$check->check();

		$this->assertTrue($check->passed());
	}

	/**
	 * @return void
	 */
	public function testLevel(): void {
		$check = new DatabaseCharsetCheck();
		$this->assertSame('info', $check->level());
	}

}
