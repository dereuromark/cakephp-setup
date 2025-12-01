<?php

namespace Setup\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Check;

class MemoryLimitCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if PHP memory_limit is configured appropriately.';

	protected string $level = self::LEVEL_WARNING;

	protected int $minMemoryMB;

	protected int $maxMemoryMB;

	/**
	 * @param int $minMemoryMB Minimum recommended memory limit in MB (default: 128)
	 * @param int $maxMemoryMB Maximum reasonable memory limit in MB (default: 1024)
	 */
	public function __construct(int $minMemoryMB = 128, int $maxMemoryMB = 1024) {
		$this->minMemoryMB = $minMemoryMB;
		$this->maxMemoryMB = $maxMemoryMB;
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->passed = true;

		$memoryLimit = ini_get('memory_limit');

		// Check for unlimited memory
		if ($memoryLimit === '-1') {
			$this->warningMessage[] = 'memory_limit is set to unlimited (-1). This can lead to resource exhaustion and server instability.';
			$this->warningMessage[] = 'Set a reasonable limit like ' . ($this->minMemoryMB * 2) . 'M or ' . ($this->minMemoryMB * 4) . 'M based on your application needs.';
			$this->passed = false;
			$this->addFixInstructions();

			return;
		}

		// Parse memory limit to MB
		$memoryLimitBytes = $this->parseMemorySize($memoryLimit);
		$memoryLimitMB = $memoryLimitBytes / 1024 / 1024;

		$this->infoMessage[] = 'Memory limit: ' . $memoryLimitMB . ' MB';

		// Check if too low
		if ($memoryLimitMB < $this->minMemoryMB) {
			$this->warningMessage[] = 'memory_limit is ' . $memoryLimitMB . ' MB. This is quite low for modern PHP applications.';
			$this->warningMessage[] = 'Consider increasing to at least ' . ($this->minMemoryMB * 2) . 'M for typical applications.';
			$this->passed = false;
		}

		// Check if unusually high (but not unlimited)
		if ($memoryLimitMB > $this->maxMemoryMB) {
			$this->infoMessage[] = 'Memory limit is quite high (' . $memoryLimitMB . ' MB). Ensure this is intentional.';
		}

		// Show current memory usage
		$currentUsage = memory_get_usage(true);
		$currentUsageMB = $currentUsage / 1024 / 1024;
		$peakUsage = memory_get_peak_usage(true);
		$peakUsageMB = $peakUsage / 1024 / 1024;

		$this->infoMessage[] = sprintf('Current memory usage: %.2f MB (peak: %.2f MB)', $currentUsageMB, $peakUsageMB);

		if (!$this->passed) {
			$this->addFixInstructions();
		}
	}

	/**
	 * Add helpful information about how to configure memory limit.
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

		$this->infoMessage[] = 'To configure memory limit:';
		$this->infoMessage[] = '1. Set memory_limit in php.ini: memory_limit=' . ($this->minMemoryMB * 2) . 'M (recommended for most apps)';
		$this->infoMessage[] = '2. For large applications: memory_limit=' . ($this->minMemoryMB * 4) . 'M';
		$this->infoMessage[] = '3. Avoid unlimited (-1) as it can cause server instability';

		if ($phpIniPath) {
			$this->infoMessage[] = 'Quick fix via sed:';
			$this->infoMessage[] = '  `sudo sed -i \'s/^;\\?memory_limit.*/memory_limit = ' . ($this->minMemoryMB * 2) . 'M/\' ' . $phpIniPath . '`';
		}

		$this->infoMessage[] = 'After editing, restart your web server (Apache/Nginx) or PHP-FPM to apply changes.';
		$this->infoMessage[] = 'Note: CLI scripts can have different limits set in php-cli.ini';
	}

	/**
	 * Parse memory size string (e.g., "64M", "128K") to bytes.
	 *
	 * @param string|int $size Memory size
	 * @return float Size in bytes
	 */
	protected function parseMemorySize(string|int $size): float {
		if (is_numeric($size)) {
			return (float)$size;
		}

		$size = trim($size);
		$unit = strtoupper(substr($size, -1));
		$value = (float)substr($size, 0, -1);

		return match ($unit) {
			'G' => $value * 1024 * 1024 * 1024,
			'M' => $value * 1024 * 1024,
			'K' => $value * 1024,
			default => $value,
		};
	}

}
