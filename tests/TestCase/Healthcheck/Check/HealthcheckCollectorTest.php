<?php

namespace Setup\Test\TestCase\Healthcheck\Check;

use Cake\TestSuite\TestCase;
use Setup\Healthcheck\Check\CheckInterface;
use Setup\Healthcheck\HealthcheckCollector;

class HealthcheckCollectorTest extends TestCase {

	/**
	 * @return void
	 */
	public function testDefaultChecks(): void {
		$checks = HealthcheckCollector::defaultChecks();

		$this->assertNotEmpty($checks);
		foreach ($checks as $check => $options) {
			$this->assertTrue(is_subclass_of($check, CheckInterface::class), $check . ' actually is ' . gettype($check));
			$this->assertSame([], $options, print_r($options, true));
		}
	}

}
