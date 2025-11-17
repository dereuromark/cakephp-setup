<?php

namespace Setup\Test\TestCase\Healthcheck\Check;

use Cake\TestSuite\TestCase;
use Setup\Healthcheck\Check\CheckInterface;
use Setup\Healthcheck\Check\Core\SessionCleanupCheck;
use Setup\Healthcheck\Check\Database\ConnectCheck;
use Setup\Healthcheck\Check\Environment\PhpExtensionsCheck;
use Setup\Healthcheck\Check\Environment\PhpVersionCheck;
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

	/**
	 * @return void
	 */
	public function testCheckFiltering(): void {
		$checks = [
			(new PhpVersionCheck()),
			(new PhpExtensionsCheck())->adjustPriority(1),
			(new ConnectCheck())->adjustScope([
				function () {
					return false; // disable this check
				},
			]),
		];

		$result = (new HealthcheckCollector($checks))->getChecks();

		$this->assertCount(2, $result);
		$this->assertInstanceOf(PhpExtensionsCheck::class, $result[0]);
		$this->assertInstanceOf(PhpVersionCheck::class, $result[1]);
	}

	/**
	 * @return void
	 */
	public function testSessionCleanupCheckIncluded(): void {
		$checks = HealthcheckCollector::defaultChecks();

		$this->assertArrayHasKey(SessionCleanupCheck::class, $checks);
	}

}
