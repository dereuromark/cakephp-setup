<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Environment\ExposePhpCheck;
use Shim\TestSuite\TestCase;

class ExposePhpCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new ExposePhpCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testScope(): void {
		$check = new ExposePhpCheck();
		$this->assertSame(['web', 'cli'], $check->scope());
	}

	/**
	 * @return void
	 */
	public function testCheckInDevelopmentMode(): void {
		$originalDebug = Configure::read('debug');

		Configure::write('debug', true);

		$check = new ExposePhpCheck();
		$check->check();

		$this->assertTrue($check->passed());

		Configure::write('debug', $originalDebug);
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new ExposePhpCheck();
		$check->check();

		$this->assertTrue($check->passed());
	}

	/**
	 * @return void
	 */
	public function testLevel(): void {
		$check = new ExposePhpCheck();
		$this->assertSame('info', $check->level());
	}

}
