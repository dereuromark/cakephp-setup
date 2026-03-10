<?php

declare(strict_types=1);

namespace Setup\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Setup\Utility\System;

/**
 * @uses \Setup\Utility\System
 */
class SystemTest extends TestCase {

	/**
	 * Test error2string with E_ERROR
	 *
	 * @return void
	 */
	public function testError2stringWithSingleError(): void {
		$result = System::error2string(E_ERROR);
		$this->assertSame('E_ERROR', $result);
	}

	/**
	 * Test error2string with E_ALL
	 *
	 * @return void
	 */
	public function testError2stringWithEAll(): void {
		$result = System::error2string(E_ALL);
		$this->assertStringContainsString('E_ALL', $result);
	}

	/**
	 * Test error2string with multiple errors
	 *
	 * @return void
	 */
	public function testError2stringWithMultipleErrors(): void {
		$result = System::error2string(E_ERROR | E_WARNING);
		$this->assertStringContainsString('E_ERROR', $result);
		$this->assertStringContainsString('E_WARNING', $result);
		$this->assertStringContainsString('|', $result);
	}

	/**
	 * Test error2string with showDisabled
	 *
	 * @return void
	 */
	public function testError2stringWithShowDisabled(): void {
		$result = System::error2string(E_ERROR, true);
		$this->assertStringContainsString('E_ERROR', $result);
		$this->assertStringContainsString('text-decoration:line-through', $result);
	}

	/**
	 * Test error2string without showDisabled
	 *
	 * @return void
	 */
	public function testError2stringWithoutShowDisabled(): void {
		$result = System::error2string(E_ERROR, false);
		$this->assertStringNotContainsString('line-through', $result);
	}

	/**
	 * Test string2error with E_ERROR
	 *
	 * @return void
	 */
	public function testString2errorWithSingleError(): void {
		$result = System::string2error('E_ERROR');
		$this->assertSame(E_ERROR, $result);
	}

	/**
	 * Test string2error with multiple errors
	 *
	 * @return void
	 */
	public function testString2errorWithMultipleErrors(): void {
		$result = System::string2error('E_ERROR | E_WARNING');
		$this->assertSame(E_ERROR | E_WARNING, $result);
	}

	/**
	 * Test string2error with E_ALL
	 *
	 * @return void
	 */
	public function testString2errorWithEAll(): void {
		$result = System::string2error('E_ALL');
		$this->assertSame(E_ALL, $result);
	}

	/**
	 * Test string2error with invalid value
	 *
	 * @return void
	 */
	public function testString2errorWithInvalidValue(): void {
		$result = System::string2error('INVALID_ERROR');
		$this->assertSame(0, $result);
	}

	/**
	 * Test round-trip conversion
	 *
	 * @return void
	 */
	public function testRoundTripConversion(): void {
		$original = E_ERROR | E_WARNING | E_NOTICE;
		$string = System::error2string($original);
		$back = System::string2error($string);
		$this->assertSame($original, $back);
	}

}
