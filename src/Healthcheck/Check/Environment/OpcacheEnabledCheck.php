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

	protected string $level = self::LEVEL_WARNING;

	public function __construct() {
		$this->isDebug = (bool)Configure::read('debug');
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

		if (!$this->passed) {
			$this->warningMessage[] = 'OPcache is disabled in production mode. This significantly impacts performance and should be enabled.';
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

			if (isset($directives['opcache.memory_consumption'])) {
				$this->infoMessage[] = 'Memory consumption: ' . ($directives['opcache.memory_consumption'] / 1024 / 1024) . ' MB';
			}

			if (isset($directives['opcache.max_accelerated_files'])) {
				$this->infoMessage[] = 'Max accelerated files: ' . $directives['opcache.max_accelerated_files'];
			}

			if (isset($directives['opcache.validate_timestamps']) && $directives['opcache.validate_timestamps']) {
				$revalidateFreq = $directives['opcache.revalidate_freq'] ?? 2;
				if ($revalidateFreq < 60) {
					$this->warningMessage[] = 'opcache.revalidate_freq is set to ' . $revalidateFreq . ' seconds. For production, consider increasing to 60+ seconds for better performance.';
				}
			}

			if (isset($directives['opcache.validate_timestamps']) && !$directives['opcache.validate_timestamps']) {
				$this->infoMessage[] = 'Timestamp validation is disabled (optimal for production).';
			}
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
				$this->warningMessage[] = 'OPcache has high memory waste (' . round($wastedPercent, 2) . '%). Consider increasing opcache.memory_consumption or restarting PHP.';
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
					$this->warningMessage[] = 'OPcache hit rate is below 90%. This may indicate the cache is being cleared frequently or memory is insufficient.';
				}
			}
		}
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
		$this->infoMessage[] = '2. Enable OPcache: opcache.enable=1';
		$this->infoMessage[] = '3. For CLI scripts: opcache.enable_cli=1 (optional)';
		$this->infoMessage[] = 'Recommended production settings:';
		$this->infoMessage[] = '  opcache.memory_consumption=128 (or higher based on your app size)';
		$this->infoMessage[] = '  opcache.interned_strings_buffer=8';
		$this->infoMessage[] = '  opcache.max_accelerated_files=10000';
		$this->infoMessage[] = '  opcache.validate_timestamps=0 (disable for best performance, requires cache clear on deploy)';
		$this->infoMessage[] = '  opcache.revalidate_freq=60 (if validate_timestamps=1)';
		$this->infoMessage[] = 'After editing, restart your web server (Apache/Nginx) or PHP-FPM to apply changes.';
		$this->infoMessage[] = 'Performance impact: OPcache can improve PHP performance by 2-3x in production.';
	}

}
