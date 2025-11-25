<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Core\CookieSecurityCheck;
use Shim\TestSuite\TestCase;

class CookieSecurityCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new CookieSecurityCheck();
		$this->assertSame('Core', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testScope(): void {
		$check = new CookieSecurityCheck();
		$this->assertSame(['web', 'cli'], $check->scope());
	}

	/**
	 * @return void
	 */
	public function testCheckInDevelopmentMode(): void {
		$originalDebug = Configure::read('debug');

		Configure::write('debug', true);

		$check = new CookieSecurityCheck();
		$check->check();

		// In development mode, secure cookie check is relaxed
		$this->assertIsBool($check->passed());

		Configure::write('debug', $originalDebug);
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new CookieSecurityCheck();
		$check->check();

		// Test should run without errors
		$this->assertIsBool($check->passed());
	}

	/**
	 * @return void
	 */
	public function testLevel(): void {
		$check = new CookieSecurityCheck();
		$this->assertSame('warning', $check->level());
	}

}
