<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Environment\DisableFunctionsCheck;
use Shim\TestSuite\TestCase;

class DisableFunctionsCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new DisableFunctionsCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheckInDevelopmentMode(): void {
		$originalDebug = Configure::read('debug');

		// Enable debug mode (development)
		Configure::write('debug', true);

		$check = new DisableFunctionsCheck();
		$check->check();

		// In development mode, check should always pass
		$this->assertTrue($check->passed());

		// Restore original debug setting
		Configure::write('debug', $originalDebug);
	}

	/**
	 * @return void
	 */
	public function testCheckInProductionMode(): void {
		$originalDebug = Configure::read('debug');

		// Disable debug mode (production)
		Configure::write('debug', false);

		$check = new DisableFunctionsCheck();
		$check->check();

		// Result depends on current PHP configuration
		// Just verify the check runs without error and produces info messages
		$this->assertNotNull($check->passed());
		$this->assertNotEmpty($check->infoMessage());

		// Restore original debug setting
		Configure::write('debug', $originalDebug);
	}

	/**
	 * @return void
	 */
	public function testLevel(): void {
		$check = new DisableFunctionsCheck();
		$this->assertSame('info', $check->level());
	}

}
