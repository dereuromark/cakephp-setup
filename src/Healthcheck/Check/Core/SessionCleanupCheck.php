<?php

namespace Setup\Healthcheck\Check\Core;

use Setup\Healthcheck\Check\Check;

class SessionCleanupCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if session garbage collection is properly configured to clean up expired sessions.';

	/**
	 * @var array<string>
	 */
	protected array $scope = [
		self::SCOPE_WEB,
	];

	/**
	 * @return void
	 */
	public function check(): void {
		$this->assertGarbageCollection();
	}

	/**
	 * @return void
	 */
	protected function assertGarbageCollection(): void {
		$gcProbability = (int)ini_get('session.gc_probability');
		$gcDivisor = (int)ini_get('session.gc_divisor');

		$this->passed = true;

		// Check if gc_probability is greater than 0 (GC enabled)
		if ($gcProbability <= 0) {
			$this->failureMessage[] = 'Session garbage collection is disabled. The session.gc_probability is set to `' . $gcProbability . '`, but must be greater than 0 for automatic session cleanup to work.';
			$this->failureMessage[] = 'Without garbage collection, expired sessions will accumulate and never be cleaned up automatically.';

			$this->passed = false;
		}

		// Check if gc_divisor is valid
		if ($gcDivisor <= 0) {
			$this->failureMessage[] = 'The session.gc_divisor is set to `' . $gcDivisor . '`, but must be greater than 0. This would cause invalid probability calculation.';

			$this->passed = false;
		}

		// Only perform additional checks if basic validation passed
		if ($this->passed) {
			// Check if gc_probability is greater than 1 (recommended for better cleanup)
			if ($gcProbability === 1) {
				$this->warningMessage[] = 'The session.gc_probability is set to only `1`. While this enables garbage collection, a higher value (e.g., 1-100) may be more appropriate depending on your gc_divisor setting.';
			}

			// Calculate and display effective probability
			$effectiveProbability = ($gcProbability / $gcDivisor) * 100;
			$this->infoMessage[] = sprintf(
				'Session garbage collection probability: %d/%d = %.4f%% (runs on ~1 in %d requests)',
				$gcProbability,
				$gcDivisor,
				$effectiveProbability,
				$gcDivisor > 0 ? (int)ceil($gcDivisor / $gcProbability) : 0,
			);

			// Provide guidance on probability settings
			if ($effectiveProbability < 0.01) {
				$this->infoMessage[] = 'Very low cleanup probability (< 0.01%). This is typical for high-traffic sites where cleanup overhead must be minimized.';
			} elseif ($effectiveProbability > 10) {
				$this->warningMessage[] = 'High cleanup probability (> 10%). This may cause performance issues on high-traffic sites. Consider lowering gc_probability or increasing gc_divisor.';
			} else {
				$this->infoMessage[] = 'Cleanup probability is within a reasonable range for most applications.';
			}
		}
	}

}
