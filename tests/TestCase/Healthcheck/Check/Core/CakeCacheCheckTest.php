<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Core\CakeCacheCheck;
use Shim\TestSuite\TestCase;

class CakeCacheCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new CakeCacheCheck();
		$this->assertSame('Core', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheck() {
		$check = new CakeCacheCheck();

		$check->check();
		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
	}

	/**
	 * @return void
	 */
	public function testCheckTooLow() {
		Configure::write('Healthcheck.checkCacheKeys', ['xyz']);

		$check = new CakeCacheCheck();
		$check->check();
		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));

		$this->assertSame(['The following cache setups are missing: xyz.'], $check->failureMessage());
	}

}
