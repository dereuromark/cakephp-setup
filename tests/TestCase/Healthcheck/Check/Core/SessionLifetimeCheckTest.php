<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Core\SessionLifetimeCheck;
use Shim\TestSuite\TestCase;

class SessionLifetimeCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new SessionLifetimeCheck();
		$this->assertSame('Core', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheck() {
		Configure::delete('Session.timeout');

		$check = new SessionLifetimeCheck(1);

		$check->check();
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
	}

	/**
	 * @return void
	 */
	public function testCheckTooLow() {
		Configure::delete('Session.timeout');

		$check = new SessionLifetimeCheck(20000);

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertNotEmpty($check->failureMessage());
	}

	/**
	 * @return void
	 */
	public function testCheckWithCakeSessionTimeout() {
		Configure::write('Session.timeout', 20);

		$check = new SessionLifetimeCheck(1);

		$check->check();
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertNotEmpty($check->infoMessage());
	}

	/**
	 * @return void
	 */
	public function testCheckWithMismatchedSettings() {
		// Set CakePHP timeout higher than typical PHP session.gc_maxlifetime
		Configure::write('Session.timeout', 20000);

		$check = new SessionLifetimeCheck(1);

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertNotEmpty($check->warningMessage());
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		Configure::delete('Session.timeout');
	}

}
