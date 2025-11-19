<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Environment\XdebugDisabledCheck;
use Shim\TestSuite\TestCase;

class XdebugDisabledCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new XdebugDisabledCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheckInDevelopmentMode(): void {
		$originalDebug = Configure::read('debug');

		// Enable debug mode (development)
		Configure::write('debug', true);

		$check = new XdebugDisabledCheck();
		$check->check();

		// In development mode, check should always pass regardless of xdebug
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));

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

		$check = new XdebugDisabledCheck();
		$check->check();

		$xdebugEnabled = extension_loaded('xdebug');

		if ($xdebugEnabled) {
			// In production with xdebug enabled, check should fail
			$this->assertFalse($check->passed(), 'Check should fail when xdebug is enabled in production mode');
			$this->assertNotEmpty($check->warningMessage());
			$this->assertStringContainsString('Xdebug is enabled in production mode', $check->warningMessage()[0]);
			$this->assertNotEmpty($check->infoMessage());
		} else {
			// In production without xdebug, check should pass
			$this->assertTrue($check->passed(), 'Check should pass when xdebug is disabled in production mode');
		}

		// Restore original debug setting
		Configure::write('debug', $originalDebug);
	}

	/**
	 * @return void
	 */
	public function testLevel(): void {
		$check = new XdebugDisabledCheck();
		$this->assertSame('warning', $check->level());
	}

}
