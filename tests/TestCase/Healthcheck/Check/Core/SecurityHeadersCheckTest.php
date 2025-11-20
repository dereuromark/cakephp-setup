<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Core\SecurityHeadersCheck;
use Shim\TestSuite\TestCase;

class SecurityHeadersCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new SecurityHeadersCheck();
		$this->assertSame('Core', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testScope(): void {
		$check = new SecurityHeadersCheck();
		$this->assertSame(['web'], $check->scope());
	}

	/**
	 * @return void
	 */
	public function testCheckInDevelopmentMode(): void {
		$originalDebug = Configure::read('debug');

		Configure::write('debug', true);

		$check = new SecurityHeadersCheck();
		$check->check();

		// In development mode, check should always pass
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));

		Configure::write('debug', $originalDebug);
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new SecurityHeadersCheck();
		$check->check();

		// Test should run without errors
		$this->assertIsBool($check->passed());
	}

}
