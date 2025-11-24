<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Core\FullBaseUrlCheck;
use Shim\TestSuite\TestCase;

class FullBaseUrlCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new FullBaseUrlCheck();
		$this->assertSame('Core', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheck() {
		$check = new FullBaseUrlCheck();

		Configure::write('App.fullBaseUrl', 'https://example.com');

		$check->check();
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
	}

	/**
	 * @return void
	 */
	public function testCheckMissing() {
		$check = new FullBaseUrlCheck();

		Configure::write('App.fullBaseUrl', '');

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertNotEmpty($check->failureMessage());
		$this->assertStringContainsString('Host Header Injection', implode(' ', $check->failureMessage()));
	}

	/**
	 * @return void
	 */
	public function testCheckHttpInProduction() {
		$check = new FullBaseUrlCheck();

		Configure::write('debug', false);
		Configure::write('App.fullBaseUrl', 'http://example.com');

		$check->check();
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
		$this->assertNotEmpty($check->warningMessage());
		$this->assertStringContainsString('HTTPS', implode(' ', $check->warningMessage()));
	}

	/**
	 * @return void
	 */
	public function testCheckLocalhostInProduction() {
		$check = new FullBaseUrlCheck();

		Configure::write('debug', false);
		Configure::write('App.fullBaseUrl', 'https://localhost');

		$check->check();
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
		$this->assertNotEmpty($check->warningMessage());
		$this->assertStringContainsString('localhost', implode(' ', $check->warningMessage()));
	}

	/**
	 * @return void
	 */
	public function testCheckValidHttpsProduction() {
		$check = new FullBaseUrlCheck();

		Configure::write('debug', false);
		Configure::write('App.fullBaseUrl', 'https://example.com');

		$check->check();
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
		$this->assertEmpty($check->warningMessage());
	}

	/**
	 * Test detection of Host Header Injection via runtime check (simulates web mode)
	 *
	 * @return void
	 */
	public function testCheckHostHeaderInjectionDetection() {
		$check = new FullBaseUrlCheck();

		// We can't actually change PHP_SAPI, so we test the isVulnerableToHostHeaderInjection method directly
		$reflection = new \ReflectionClass($check);
		$method = $reflection->getMethod('isVulnerableToHostHeaderInjection');
		$method->setAccessible(true);

		// Test 1: Matching HTTP_HOST should detect vulnerability
		$_SERVER['HTTP_HOST'] = 'attacker.com';
		$result = $method->invoke($check, 'https://attacker.com');
		$this->assertTrue($result, 'Should detect vulnerability when fullBaseUrl matches HTTP_HOST');

		// Test 2: Different domain should not flag
		$_SERVER['HTTP_HOST'] = 'attacker.com';
		$result = $method->invoke($check, 'https://legitimate-domain.com');
		$this->assertFalse($result, 'Should not flag vulnerability when fullBaseUrl differs from HTTP_HOST');

		// Test 3: Matching with non-standard port
		$_SERVER['HTTP_HOST'] = 'attacker.com:8080';
		$result = $method->invoke($check, 'https://attacker.com:8080');
		$this->assertTrue($result, 'Should detect vulnerability with non-standard port');

		// Test 4: HTTP with port 80 (default port should be omitted from comparison)
		$_SERVER['HTTP_HOST'] = 'attacker.com';
		$result = $method->invoke($check, 'http://attacker.com:80');
		$this->assertTrue($result, 'Should detect vulnerability when default port 80 is specified in URL');

		// Test 5: HTTPS with port 443 (default port should be omitted from comparison)
		$_SERVER['HTTP_HOST'] = 'attacker.com';
		$result = $method->invoke($check, 'https://attacker.com:443');
		$this->assertTrue($result, 'Should detect vulnerability when default port 443 is specified in URL');

		unset($_SERVER['HTTP_HOST']);
	}

	/**
	 * Test that CLI mode skips the Host Header Injection check
	 *
	 * @return void
	 */
	public function testCheckSkipsHostHeaderCheckInCliMode() {
		$check = new FullBaseUrlCheck();

		// Even if HTTP_HOST would match, CLI mode should skip the check
		$_SERVER['HTTP_HOST'] = 'example.com';
		Configure::write('App.fullBaseUrl', 'https://example.com');

		$check->check();

		// In CLI mode, this should pass even though they match
		$this->assertTrue($check->passed(), 'CLI mode should skip Host Header check');

		unset($_SERVER['HTTP_HOST']);
	}

}
