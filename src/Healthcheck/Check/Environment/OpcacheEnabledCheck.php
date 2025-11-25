<?php

namespace Setup\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class OpcacheEnabledCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if OPcache is enabled in production mode (when debug is off).';

	protected bool $isDebug;

	protected bool $opcacheEnabled;

	protected bool $isCli;

	protected string $level = self::LEVEL_WARNING;

	public function __construct() {
		$this->isDebug = (bool)Configure::read('debug');
		$this->isCli = PHP_SAPI === 'cli';
		$this->opcacheEnabled = function_exists('opcache_get_status') && opcache_get_status() !== false;
	}

	/**
	 * @return void
	 */
	public function check(): void {
		// If debug mode is on (development), opcache is optional
		if ($this->isDebug) {
			$this->passed = true;
			if ($this->opcacheEnabled) {
				$this->infoMessage[] = 'OPcache is enabled in development mode.';
			} else {
				$this->infoMessage[] = 'OPcache is disabled, which is acceptable in development mode.';
			}

			return;
		}

		// In production mode (debug off), opcache should be enabled
		$this->passed = $this->opcacheEnabled;
		$context = $this->isCli ? 'CLI' : 'web';

		if (!$this->passed) {
			$this->warningMessage[] = 'OPcache is disabled for ' . $context . ' in production mode. This significantly impacts performance and should be enabled.';
			$this->addFixInstructions();
		} else {
			$this->addOpcacheInfo();
		}
	}

	/**
	 * Add helpful information about OPcache configuration.
	 *
	 * @return void
	 */
	protected function addOpcacheInfo(): void {
		$status = opcache_get_status();
		if (!$status) {
			return;
		}

		$this->infoMessage[] = 'OPcache is enabled and running.';

		// Display key configuration values
		$config = opcache_get_configuration();
		if (isset($config['directives'])) {
			$directives = $config['directives'];

			$this->checkMemoryConfiguration($directives);
			$this->checkFileConfiguration($directives);
			$this->checkTimestampConfiguration($directives);
			$this->checkJitConfiguration($directives);
		}

		// Display usage statistics
		if (isset($status['memory_usage'])) {
			$memUsed = $status['memory_usage']['used_memory'] / 1024 / 1024;
			$memFree = $status['memory_usage']['free_memory'] / 1024 / 1024;
			$memWasted = $status['memory_usage']['wasted_memory'] / 1024 / 1024;
			$wastedPercent = $status['memory_usage']['current_wasted_percentage'];

			$this->infoMessage[] = sprintf(
				'Memory usage: %.2f MB used, %.2f MB free, %.2f MB wasted (%.2f%%)',
				$memUsed,
				$memFree,
				$memWasted,
				$wastedPercent,
			);

			if ($wastedPercent > 10) {
				$this->infoMessage[] = 'OPcache has high memory waste (' . round($wastedPercent, 2) . '%). Consider increasing opcache.memory_consumption or restarting PHP.';
			}
		}

		if (isset($status['opcache_statistics'])) {
			$stats = $status['opcache_statistics'];
			if (isset($stats['num_cached_scripts'])) {
				$this->infoMessage[] = 'Cached scripts: ' . $stats['num_cached_scripts'];
			}

			if (isset($stats['hits'], $stats['misses']) && ($stats['hits'] + $stats['misses']) > 0) {
				$hitRate = ($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100;
				$this->infoMessage[] = sprintf('Hit rate: %.2f%% (%d hits, %d misses)', $hitRate, $stats['hits'], $stats['misses']);

				if ($hitRate < 90) {
					$this->infoMessage[] = 'OPcache hit rate is below 90%. This may indicate the cache is being cleared frequently or memory is insufficient.';
				}
			}
		}
	}

	/**
	 * Check memory-related configuration.
	 *
	 * @param array $directives OPcache directives
	 * @return void
	 */
	protected function checkMemoryConfiguration(array $directives): void {
		if (isset($directives['opcache.memory_consumption'])) {
			$memoryMB = $directives['opcache.memory_consumption'] / 1024 / 1024;
			$this->infoMessage[] = 'Memory consumption: ' . $memoryMB . ' MB';

			if ($memoryMB < 256) {
				$this->infoMessage[] = 'opcache.memory_consumption is ' . $memoryMB . ' MB. For large applications, consider 256-512 MB.';
			}
		}

		if (isset($directives['opcache.interned_strings_buffer'])) {
			$stringBufferMB = $directives['opcache.interned_strings_buffer'];
			$this->infoMessage[] = 'Interned strings buffer: ' . $stringBufferMB . ' MB';

			if ($stringBufferMB < 16) {
				$this->infoMessage[] = 'opcache.interned_strings_buffer is ' . $stringBufferMB . ' MB. Consider increasing to 16-32 MB for better string optimization.';
			}
		}
	}

	/**
	 * Check file-related configuration.
	 *
	 * @param array $directives OPcache directives
	 * @return void
	 */
	protected function checkFileConfiguration(array $directives): void {
		if (isset($directives['opcache.max_accelerated_files'])) {
			$maxFiles = $directives['opcache.max_accelerated_files'];
			$this->infoMessage[] = 'Max accelerated files: ' . $maxFiles;

			if ($maxFiles < 20000) {
				$this->infoMessage[] = 'opcache.max_accelerated_files is ' . $maxFiles . '. For large applications, consider 20000+.';
			}
		}

		if (isset($directives['opcache.enable_file_override'])) {
			if (!$directives['opcache.enable_file_override']) {
				$this->infoMessage[] = 'opcache.enable_file_override is disabled. Enable it for better performance with file_exists(), is_file(), etc.';
			}
		}
	}

	/**
	 * Check timestamp validation configuration.
	 *
	 * @param array $directives OPcache directives
	 * @return void
	 */
	protected function checkTimestampConfiguration(array $directives): void {
		if (isset($directives['opcache.validate_timestamps'])) {
			if ($directives['opcache.validate_timestamps']) {
				$revalidateFreq = $directives['opcache.revalidate_freq'] ?? 2;
				$this->warningMessage[] = 'opcache.validate_timestamps is enabled. For production, set to 0 for best performance (requires cache clear on deploy).';

				if ($revalidateFreq < 60) {
					$this->infoMessage[] = 'opcache.revalidate_freq is ' . $revalidateFreq . ' seconds. If keeping timestamps enabled, increase to 60+ seconds.';
				}
			} else {
				$this->infoMessage[] = 'Timestamp validation is disabled (optimal for production).';
			}
		}
	}

	/**
	 * Check JIT configuration.
	 *
	 * @param array $directives OPcache directives
	 * @return void
	 */
	protected function checkJitConfiguration(array $directives): void {
		if (!isset($directives['opcache.jit'])) {
			$this->infoMessage[] = 'JIT (Just-In-Time compilation) is disabled. Enable with opcache.jit=1255 for significant performance gains.';
			$this->infoMessage[] = 'JIT can provide 2-3x performance improvement for CPU-intensive operations.';

			return;
		}

		$jitMode = $directives['opcache.jit'];

		// Check if JIT is effectively disabled
		if (!$jitMode || $jitMode === 'disable') {
			$this->infoMessage[] = 'JIT (Just-In-Time compilation) is disabled. Enable with opcache.jit=1255 for significant performance gains.';
			$this->infoMessage[] = 'JIT can provide 2-3x performance improvement for CPU-intensive operations.';

			return;
		}

		$this->infoMessage[] = 'JIT is enabled with mode: ' . $jitMode;

		if (isset($directives['opcache.jit_buffer_size'])) {
			$jitBufferSize = $directives['opcache.jit_buffer_size'];

			// Convert to MB if it's a numeric value
			if (is_numeric($jitBufferSize)) {
				$jitBufferMB = $jitBufferSize / 1024 / 1024;
			} else {
				// Parse size strings like "64M"
				$jitBufferMB = $this->parseMemorySize($jitBufferSize);
			}

			$this->infoMessage[] = 'JIT buffer size: ' . $jitBufferMB . ' MB';

			if ($jitBufferMB < 128) {
				$this->infoMessage[] = 'opcache.jit_buffer_size is ' . $jitBufferMB . ' MB. Consider 128-256 MB for optimal JIT performance.';
			}
		}

		// Validate JIT mode
		if (is_numeric($jitMode) || (is_string($jitMode) && ctype_digit($jitMode))) {
			$jitModeNum = (int)$jitMode;
			if ($jitModeNum === 1205 || $jitModeNum === 1255) {
				$this->infoMessage[] = 'JIT mode ' . $jitModeNum . ' is recommended for most applications.';
			} elseif ($jitModeNum < 1200) {
				$this->infoMessage[] = 'JIT mode ' . $jitModeNum . ' may not be optimal. Consider 1255 (tracing JIT) or 1205 for most applications.';
			}
		}
	}

	/**
	 * Parse memory size string (e.g., "64M", "128K") to MB.
	 *
	 * @param string|int $size Memory size
	 * @return float Size in MB
	 */
	protected function parseMemorySize(string|int $size): float {
		if (is_numeric($size)) {
			return $size / 1024 / 1024;
		}

		$size = trim($size);
		$unit = strtoupper(substr($size, -1));
		$value = (float)substr($size, 0, -1);

		return match ($unit) {
			'G' => $value * 1024,
			'M' => $value,
			'K' => $value / 1024,
			default => $value / 1024 / 1024,
		};
	}

	/**
	 * Add helpful information about how to enable OPcache.
	 *
	 * @return void
	 */
	protected function addFixInstructions(): void {
		$phpIniPath = php_ini_loaded_file();

		if ($phpIniPath) {
			$this->infoMessage[] = 'Loaded Configuration File: `' . $phpIniPath . '`';

			$scannedFiles = php_ini_scanned_files();
			if ($scannedFiles) {
				$scannedFilesList = array_map('trim', explode(',', $scannedFiles));
				$this->infoMessage[] = 'Additional .ini files parsed: ' . count($scannedFilesList) . ' file(s)';
				$this->infoMessage[] = 'Check for opcache configuration in these files as well.';
			}
		} else {
			$this->infoMessage[] = 'PHP configuration file location not found. Run `php --ini` to locate your php.ini file.';
		}

		$this->infoMessage[] = 'To enable OPcache:';
		$this->infoMessage[] = '1. Ensure the OPcache extension is loaded: zend_extension=opcache.so (or opcache.dll on Windows)';

		if ($this->isCli) {
			$this->infoMessage[] = '2. Enable OPcache for CLI: opcache.enable_cli=1';
		} else {
			$this->infoMessage[] = '2. Enable OPcache: opcache.enable=1';
		}

		$this->infoMessage[] = 'Recommended production settings:';
		$this->infoMessage[] = '  opcache.memory_consumption=256 (256-512 MB for large apps)';
		$this->infoMessage[] = '  opcache.interned_strings_buffer=16 (16-32 MB recommended)';
		$this->infoMessage[] = '  opcache.max_accelerated_files=20000 (20000+ for large apps)';
		$this->infoMessage[] = '  opcache.validate_timestamps=0 (disable for best performance, requires cache clear on deploy)';
		$this->infoMessage[] = '  opcache.revalidate_freq=0 (if validate_timestamps=1, use 60+)';
		$this->infoMessage[] = '  opcache.enable_file_override=1 (optimization for file functions)';

		$this->infoMessage[] = 'JIT settings:';
		$this->infoMessage[] = '  opcache.jit=1255 (tracing JIT, recommended for most apps)';
		$this->infoMessage[] = '  opcache.jit_buffer_size=128M (128-256 MB recommended)';
		$this->infoMessage[] = 'JIT modes: 1205 (function JIT) or 1255 (tracing JIT) are recommended';

		if (!$this->isCli) {
			$this->infoMessage[] = 'After editing, restart your web server (Apache/Nginx) or PHP-FPM to apply changes.';
		}
		$this->infoMessage[] = 'Performance impact: OPcache can improve PHP performance by 2-3x, JIT adds another 2-3x for CPU-intensive code.';
	}

}
