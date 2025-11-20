<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Core;

use Setup\Healthcheck\Check\Core\FilePermissionsCheck;
use Shim\TestSuite\TestCase;

class FilePermissionsCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new FilePermissionsCheck();
		$this->assertSame('Core', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new FilePermissionsCheck();
		$check->check();

		// Test should run without errors
		$this->assertIsBool($check->passed());
	}

	/**
	 * @return void
	 */
	public function testCheckCustomDirectories(): void {
		$check = new FilePermissionsCheck(['vendor']);

		$check->check();

		// Vendor directory should exist and be readable
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
	}

}
