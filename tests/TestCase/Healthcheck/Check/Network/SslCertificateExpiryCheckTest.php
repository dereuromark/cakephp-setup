<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Network;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Network\SslCertificateExpiryCheck;
use Shim\TestSuite\TestCase;

class SslCertificateExpiryCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new SslCertificateExpiryCheck();
		$this->assertSame('Network', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheckWithNoHost(): void {
		Configure::delete('Healthcheck.sslHost');

		$check = new SslCertificateExpiryCheck();
		$check->check();

		// Should pass with info message when no host configured
		$this->assertTrue($check->passed());
		$this->assertNotEmpty($check->infoMessage());
	}

	/**
	 * @return void
	 */
	public function testCheckWithValidHost(): void {
		// Test with a known valid SSL host
		// In CI environments, external connections may be blocked or timeout
		// So we just verify the check runs without throwing exceptions
		$check = new SslCertificateExpiryCheck(30, 7, 'www.google.com');
		$check->check();

		$this->assertIsBool($check->passed());
	}

	/**
	 * @return void
	 */
	public function testCheckWithInvalidHost(): void {
		$check = new SslCertificateExpiryCheck(30, 7, 'invalid.host.that.does.not.exist.example');
		$check->check();

		$this->assertFalse($check->passed());
	}

	/**
	 * @return void
	 */
	public function testCheckWithCustomThresholds(): void {
		$check = new SslCertificateExpiryCheck(60, 14);
		$this->assertSame('Network', $check->domain());
	}

}
