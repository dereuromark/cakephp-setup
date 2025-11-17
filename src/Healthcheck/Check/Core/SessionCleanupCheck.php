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

		// Add fix instructions if check failed
		if (!$this->passed) {
			$this->addFixInstructions($gcProbability, $gcDivisor);

			return;
		}

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

	/**
	 * Add helpful information about how to fix the session garbage collection issue.
	 *
	 * @param int $gcProbability Current gc_probability value
	 * @param int $gcDivisor Current gc_divisor value
	 * @return void
	 */
	protected function addFixInstructions(int $gcProbability, int $gcDivisor): void {
		$phpIniPath = php_ini_loaded_file();

		if ($phpIniPath) {
			$this->infoMessage[] = 'Loaded Configuration File: `' . $phpIniPath . '`';

			$scannedFiles = php_ini_scanned_files();
			if ($scannedFiles) {
				$scannedFilesList = array_map('trim', explode(',', $scannedFiles));
				$this->infoMessage[] = 'Additional .ini files parsed: ' . count($scannedFilesList) . ' file(s)';
			}

			$this->infoMessage[] = 'Edit the file and set: session.gc_probability = 1 and session.gc_divisor = 1000';
			$this->infoMessage[] = 'Quick fix using sed: sed -i "s/^session.gc_probability = .*/session.gc_probability = 1/" ' . escapeshellarg($phpIniPath) . ' && sed -i "s/^session.gc_divisor = .*/session.gc_divisor = 1000/" ' . escapeshellarg($phpIniPath);
			$this->infoMessage[] = 'This gives a 0.1% cleanup probability (runs on ~1 in 1000 requests) - adjust as needed.';
			$this->infoMessage[] = 'After editing, restart your web server (Apache/Nginx) or PHP-FPM to apply changes.';
		} else {
			$this->infoMessage[] = 'PHP configuration file location not found. Run `php --ini` to locate your php.ini file.';
		}
	}

}
