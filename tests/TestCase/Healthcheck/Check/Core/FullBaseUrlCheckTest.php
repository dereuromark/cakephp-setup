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
	}

}
