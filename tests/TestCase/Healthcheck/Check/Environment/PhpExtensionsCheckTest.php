<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Environment\PhpExtensionsCheck;
use Shim\TestSuite\TestCase;

class PhpExtensionsCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new PhpExtensionsCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheck() {
		$check = new PhpExtensionsCheck();

		$check->check();
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
	}

	/**
	 * @return void
	 */
	public function testCheckFail() {
		$check = new PhpExtensionsCheck(['nonexistent_extension']);

		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertNotEmpty($check->failureMessage());
	}

}
