<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Core;

use Cake\Utility\Security;
use Setup\Healthcheck\Check\Core\CakeSaltCheck;
use Shim\TestSuite\TestCase;

class CakeSaltCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new CakeSaltCheck();
		$this->assertSame('Core', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheckWithLongSalt(): void {
		$originalSalt = Security::getSalt();

		Security::setSalt(str_repeat('a', 64));

		$check = new CakeSaltCheck();
		$check->check();

		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));

		Security::setSalt($originalSalt);
	}

	/**
	 * @return void
	 */
	public function testCheckWithShortSalt(): void {
		$originalSalt = Security::getSalt();

		Security::setSalt('short');

		$check = new CakeSaltCheck();
		$check->check();

		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));
		$this->assertNotEmpty($check->warningMessage());

		Security::setSalt($originalSalt);
	}

	/**
	 * @return void
	 */
	public function testCheckNotConfigured(): void {
		$originalSalt = Security::getSalt();

		Security::setSalt('__SALT__');

		$check = new CakeSaltCheck();
		$check->check();

		$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));
		$this->assertNotEmpty($check->failureMessage());

		Security::setSalt($originalSalt);
	}

}
