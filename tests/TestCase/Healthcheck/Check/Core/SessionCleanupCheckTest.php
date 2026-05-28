<?php

namespace Setup\Test\TestCase\Healthcheck\Check\Core;

use Setup\Healthcheck\Check\Core\SessionCleanupCheck;
use Shim\TestSuite\TestCase;

class SessionCleanupCheckTest extends TestCase {

	protected function createCheck(bool $hasExternalSessionCleanup = false): SessionCleanupCheck {
		return new class ($hasExternalSessionCleanup) extends SessionCleanupCheck {

			public function __construct(protected bool $hasExternalSessionCleanup) {
			}

			protected function hasExternalSessionCleanup(string $saveHandler, string $savePath): bool {
				return $this->hasExternalSessionCleanup;
			}

		};
	}

	/**
	 * @return void
	 */
	public function testDomain(): void {
		$check = new SessionCleanupCheck();
		$this->assertSame('Core', $check->domain());
	}

	/**
	 * @return void
	 */
	public function testScope(): void {
		$check = new SessionCleanupCheck();
		$this->assertSame(['web'], $check->scope());
	}

	/**
	 * @return void
	 */
	public function testCheckPassed(): void {
		$check = $this->createCheck(false);

		$check->check();

		// As long as gc_probability > 0 and gc_divisor > 0, the check should pass
		$gcProbability = (int)ini_get('session.gc_probability');
		$gcDivisor = (int)ini_get('session.gc_divisor');

		if ($gcProbability > 0 && $gcDivisor > 0) {
			$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
			$this->assertNotEmpty($check->infoMessage());
		} else {
			$this->assertFalse($check->passed(), print_r($check->__debugInfo(), true));
			$this->assertNotEmpty($check->failureMessage());
		}
	}

	/**
	 * @return void
	 */
	public function testCheckGcProbabilityGreaterThanZero(): void {
		$check = $this->createCheck(false);
		$check->check();

		$gcProbability = (int)ini_get('session.gc_probability');

		if ($gcProbability > 0) {
			// Should pass when gc_probability > 0
			$this->assertTrue($check->passed(), 'Check should pass when gc_probability > 0');
		} else {
			$this->assertFalse($check->passed(), 'Check should fail when gc_probability = 0');
			$this->assertNotEmpty($check->failureMessage());
			$this->assertStringContainsString('garbage collection is disabled', $check->failureMessage()[0]);
		}
	}

	/**
	 * @return void
	 */
	public function testCheckGcProbabilityGreaterThanOne(): void {
		$check = $this->createCheck(false);
		$check->check();

		$gcProbability = (int)ini_get('session.gc_probability');
		$gcDivisor = (int)ini_get('session.gc_divisor');

		// Only check if basic validation passes
		if ($gcProbability > 0 && $gcDivisor > 0) {
			if ($gcProbability === 1) {
				// Should have a warning when gc_probability is exactly 1
				$this->assertNotEmpty($check->warningMessage());
				$this->assertStringContainsString('set to only `1`', $check->warningMessage()[0]);
			} else {
				// When gc_probability > 1, no warning about value being too low
				$warningMessages = implode(' ', $check->warningMessage());
				$this->assertStringNotContainsString('set to only `1`', $warningMessages);
			}
		} else {
			$this->assertFalse($check->passed());
		}
	}

	/**
	 * @return void
	 */
	public function testInfoMessageContainsProbability(): void {
		$check = $this->createCheck(false);
		$check->check();

		$gcProbability = (int)ini_get('session.gc_probability');
		$gcDivisor = (int)ini_get('session.gc_divisor');

		// Info message should contain probability calculation if GC is valid
		if ($gcProbability > 0 && $gcDivisor > 0) {
			$this->assertNotEmpty($check->infoMessage());
			$infoMessages = implode(' ', $check->infoMessage());
			$this->assertStringContainsString('probability', $infoMessages);
		} else {
			$this->assertNotEmpty($check->failureMessage());
		}
	}

	/**
	 * @return void
	 */
	public function testCheckPassesWithExternalCleanupWhenGcProbabilityIsZero(): void {
		$gcProbability = (int)ini_get('session.gc_probability');
		if ($gcProbability > 0) {
			$this->markTestSkipped('This environment does not have session.gc_probability = 0.');
		}

		$check = $this->createCheck(true);
		$check->check();

		$this->assertTrue($check->passed(), print_r($check->__debugInfo(), true));
		$this->assertNotEmpty($check->infoMessage());
		$this->assertStringContainsString('external cleanup', implode(' ', $check->infoMessage()));
	}

}
