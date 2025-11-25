<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Core\DebugKitDisabledCheck;
use Shim\TestSuite\TestCase;

class DebugKitDisabledCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new DebugKitDisabledCheck();
		$this->assertSame('Core', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testScope(): void {
		$check = new DebugKitDisabledCheck();
		$this->assertSame(['web', 'cli'], $check->scope());
	}

	/**
	 * @return void
	 */
	public function testCheckInDevelopmentMode(): void {
		$originalDebug = Configure::read('debug');

		Configure::write('debug', true);

		$check = new DebugKitDisabledCheck();
		$check->check();

		$this->assertTrue($check->passed());

		Configure::write('debug', $originalDebug);
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new DebugKitDisabledCheck();
		$check->check();

		$this->assertIsBool($check->passed());
	}

	/**
	 * @return void
	 */
	public function testLevel(): void {
		$check = new DebugKitDisabledCheck();
		$this->assertSame('warning', $check->level());
	}

}
