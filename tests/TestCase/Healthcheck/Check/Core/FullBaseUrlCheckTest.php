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
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));
		$this->assertNotEmpty($check->failureMessage());
		$this->assertStringContainsString('HTTPS', implode(' ', $check->failureMessage()));
	}

	/**
	 * @return void
	 */
	public function testCheckLocalhostInProduction() {
		$check = new FullBaseUrlCheck();

		Configure::write('debug', false);
		Configure::write('App.fullBaseUrl', 'https://localhost');

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));
		$this->assertNotEmpty($check->failureMessage());
		$this->assertStringContainsString('localhost', implode(' ', $check->failureMessage()));
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
		$this->assertEmpty($check->failureMessage());
	}

}
