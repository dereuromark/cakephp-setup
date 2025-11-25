<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Environment\MaxInputVarsCheck;
use Shim\TestSuite\TestCase;

class MaxInputVarsCheckTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new MaxInputVarsCheck();
		$this->assertSame('Environment', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testCheck(): void {
		$check = new MaxInputVarsCheck();
		$check->check();

		$maxInputVars = (int)ini_get('max_input_vars');
		$isSufficient = $maxInputVars >= 3000;

		if ($isSufficient) {
			$this->assertTrue($check->passed());
		} else {
			$this->assertFalse($check->passed());
		}

		// Always shows current setting as info
		$this->assertNotEmpty($check->infoMessage());
		$this->assertStringContainsString('max_input_vars = ' . $maxInputVars, $check->infoMessage()[0]);
	}

	/**
	 * @return void
	 */
	public function testLevel(): void {
		$check = new MaxInputVarsCheck();
		$this->assertSame('info', $check->level());
	}

}
