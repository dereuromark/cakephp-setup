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

		$failureMessages = $check->failureMessage();
		$this->assertCount(1, $failureMessages);
		$this->assertStringContainsString('Cannot connect to database on connection `foo`:', $failureMessages[0]);
		$this->assertStringContainsString('The datasource configuration `foo` was not found', $failureMessages[0]);
	}

	/**
	 * @return void
	 */
	public function testCheckWithValidConnection() {
		$check = new ConnectCheck();

		$check->check();
		$this->assertTrue($check->passed());
		$this->assertEmpty($check->failureMessage());
		$this->assertNotEmpty($check->infoMessage());

		$infoMessages = $check->infoMessage();
		$this->assertCount(1, $infoMessages);
		$this->assertStringContainsString('Connected to', $infoMessages[0]);
	}

	/**
	 * @return void
	 */
	public function testCheckWithNullConnection() {
		$check = new ConnectCheck(null); // Should default to 'default'

		$check->check();
		$this->assertTrue($check->passed());
	}

}
