<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Environment\AssertionsCheck;
use Shim\TestSuite\TestCase;

class AssertionsCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new AssertionsCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheckInDevelopmentMode(): void {
		$originalDebug = Configure::read('debug');

		// Enable debug mode (development)
		Configure::write('debug', true);

		$check = new AssertionsCheck();
		$check->check();

		// In development mode, assertions should be enabled
		$zendAssertions = (int)ini_get('zend.assertions');
		$assertActive = (bool)ini_get('assert.active');
		$assertionsEnabled = $zendAssertions === 1 && $assertActive;

		if ($assertionsEnabled) {
			$this->assertTrue($check->passed());
		} else {
			$this->assertFalse($check->passed());
			$this->assertNotEmpty($check->warningMessage());
		}

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

		$check = new AssertionsCheck();
		$check->check();

		$zendAssertions = (int)ini_get('zend.assertions');
		$assertActive = (bool)ini_get('assert.active');
		$assertionsDisabled = $zendAssertions <= 0 && !$assertActive;

		if ($assertionsDisabled) {
			$this->assertTrue($check->passed());
		} else {
			$this->assertFalse($check->passed());
			$this->assertNotEmpty($check->infoMessage());
		}

		// Restore original debug setting
		Configure::write('debug', $originalDebug);
	}

	/**
	 * @return void
	 */
	public function testLevel(): void {
		$check = new AssertionsCheck();
		$this->assertSame('info', $check->level());
	}

}
