<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Environment\OpcacheEnabledCheck;
use Shim\TestSuite\TestCase;

class OpcacheEnabledCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new OpcacheEnabledCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheckInDevelopmentMode(): void {
		$originalDebug = Configure::read('debug');

		// Enable debug mode (development)
		Configure::write('debug', true);

		$check = new OpcacheEnabledCheck();
		$check->check();

		// In development mode, check should always pass regardless of opcache
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
		$this->assertNotEmpty($check->infoMessage());

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

		$check = new OpcacheEnabledCheck();
		$check->check();

		$opcacheEnabled = function_exists('opcache_get_status') && opcache_get_status() !== false;

		if ($opcacheEnabled) {
			// In production with opcache enabled, check should pass
			$this->assertTrue($check->passed(), 'Check should pass when opcache is enabled in production mode');
			$this->assertNotEmpty($check->infoMessage());
		} else {
			// In production without opcache, check should fail
			$this->assertFalse($check->passed(), 'Check should fail when opcache is disabled in production mode');
			$this->assertNotEmpty($check->warningMessage());
			$this->assertStringContainsString('OPcache is disabled in production mode', $check->warningMessage()[0]);
			$this->assertNotEmpty($check->infoMessage());
		}

		// Restore original debug setting
		Configure::write('debug', $originalDebug);
	}

	/**
	 * @return void
	 */
	public function testLevel(): void {
		$check = new OpcacheEnabledCheck();
		$this->assertSame('warning', $check->level());
	}

}
