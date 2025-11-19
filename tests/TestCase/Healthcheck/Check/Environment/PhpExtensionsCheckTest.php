<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use ReflectionClass;
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

	/**
	 * Test version constraint satisfaction (works with both Semver and fallback).
	 *
	 * @return void
	 */
	public function testSatisfiesVersionFallback(): void {
		$check = new PhpExtensionsCheck([], false);
		$reflection = new ReflectionClass($check);
		$method = $reflection->getMethod('satisfiesVersion');

		$phpVersion = phpversion();

		// Test wildcard
		$this->assertTrue($method->invoke($check, '*'));

		// Test caret operator with current major version
		$currentMajor = PHP_MAJOR_VERSION;
		$this->assertTrue($method->invoke($check, '^' . $currentMajor . '.0'));

		// Test caret operator should fail for older major versions
		if ($currentMajor > 7) {
			$this->assertFalse($method->invoke($check, '^7.4'));
		}

		// Test tilde operator with current version
		$majorMinor = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
		$this->assertTrue($method->invoke($check, '~' . $majorMinor));

		// Test comparison operators
		$this->assertTrue($method->invoke($check, '>=7.4'));
		$this->assertTrue($method->invoke($check, '>7.0'));
		$this->assertTrue($method->invoke($check, '>=' . $majorMinor));
	}

}
