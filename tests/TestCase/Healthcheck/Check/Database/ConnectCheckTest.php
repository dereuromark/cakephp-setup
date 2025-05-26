<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Database;

use Setup\Healthcheck\Check\Database\ConnectCheck;
use Shim\TestSuite\TestCase;

class ConnectCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new ConnectCheck();
		$this->assertSame('Database', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheck() {
		$check = new ConnectCheck();

		$check->check();
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
	}

	/**
	 * @return void
	 */
	public function testCheckInvalid() {
		$check = new ConnectCheck('foo');

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertSame(['Cannot connect to database on connection `foo`: The datasource configuration `foo` was not found.'], $check->failureMessage());
	}

}
