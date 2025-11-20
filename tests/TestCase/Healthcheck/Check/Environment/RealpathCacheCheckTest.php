<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Environment\RealpathCacheCheck;
use Shim\TestSuite\TestCase;

class RealpathCacheCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new RealpathCacheCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new RealpathCacheCheck();
		$check->check();

		// Test should run without errors
		$this->assertIsBool($check->passed());
		$this->assertNotEmpty($check->infoMessage());
	}

}
