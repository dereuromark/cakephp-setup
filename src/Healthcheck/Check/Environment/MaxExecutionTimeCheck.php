<?php

namespace Setup\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Check;

class MaxExecutionTimeCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if max_execution_time is configured appropriately for web requests.';

	protected string $level = self::LEVEL_WARNING;

	/**
	 * @var array<string>
	 */
	protected array $scope = [
		self::SCOPE_WEB,
	];

	protected int $minSeconds;

	protected int $maxSeconds;

	/**
	 * @param int $minSeconds Minimum recommended execution time (default: 30)
	 * @param int $maxSeconds Maximum recommended execution time (default: 300)
	 */
	public function __construct(int $minSeconds = 30, int $maxSeconds = 300) {
		$this->minSeconds = $minSeconds;
		$this->maxSeconds = $maxSeconds;
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->passed = true;

		$maxExecutionTime = (int)ini_get('max_execution_time');

		$this->infoMessage[] = 'Max execution time: ' . $maxExecutionTime . ' seconds';

		// Check for unlimited execution time (0)
		if ($maxExecutionTime === 0) {
			$this->warningMessage[] = 'max_execution_time is set to 0 (unlimited). This can lead to hung processes and resource exhaustion.';
			$this->warningMessage[] = 'For web requests, set a reasonable limit like ' . $this->minSeconds . '-' . ($this->maxSeconds / 5) . ' seconds.';
			$this->passed = false;
		}

		// Check if too low
		if ($maxExecutionTime > 0 && $maxExecutionTime < $this->minSeconds) {
			$this->infoMessage[] = 'Execution time is quite low (' . $maxExecutionTime . ' seconds). This may cause timeouts for slower operations.';
			$this->infoMessage[] = 'Consider increasing to at least ' . $this->minSeconds . ' seconds.';
		}

		// Check if too high
		if ($maxExecutionTime > $this->maxSeconds) {
			$this->warningMessage[] = 'max_execution_time is ' . $maxExecutionTime . ' seconds. For web requests, this is quite high.';
			$this->warningMessage[] = 'Long-running tasks should be moved to background jobs. Consider reducing to ' . $this->minSeconds . '-' . ($this->maxSeconds / 2) . ' seconds.';
			$this->passed = false;
		}

		if (!$this->passed) {
			$this->addFixInstructions();
		}
	}

	/**
	 * Add helpful information about how to configure max execution time.
	 *
	 * @return void
	 */
	protected function addFixInstructions(): void {
		$phpIniPath = php_ini_loaded_file();

		if ($phpIniPath) {
			$this->infoMessage[] = 'Loaded Configuration File: `' . $phpIniPath . '`';
		} else {
			$this->infoMessage[] = 'PHP configuration file location not found. Run `php --ini` to locate your php.ini file.';
		}

		$this->infoMessage[] = 'To configure max execution time:';
		$this->infoMessage[] = '1. For web requests: max_execution_time=' . ($this->maxSeconds / 5) . ' (' . $this->minSeconds . '-' . ($this->maxSeconds / 2) . ' seconds recommended)';
		$this->infoMessage[] = '2. For CLI scripts: Use php-cli.ini with higher/unlimited values';
		$this->infoMessage[] = '3. Move long-running tasks to background jobs (queue system)';

		if ($phpIniPath) {
			$this->infoMessage[] = 'Quick fix via sed:';
			$this->infoMessage[] = '  `sudo sed -i \'s/^;\\?max_execution_time.*/max_execution_time = ' . (int)($this->maxSeconds / 5) . '/\' ' . $phpIniPath . '`';
		}

		$this->infoMessage[] = 'After editing, restart your web server (Apache/Nginx) or PHP-FPM to apply changes.';
		$this->infoMessage[] = 'Note: This check only runs for web requests (not CLI).';
	}

}
