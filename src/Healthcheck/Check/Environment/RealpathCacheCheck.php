<?php

namespace Setup\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class RealpathCacheCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if realpath cache is properly configured for optimal file system performance.';

	protected bool $isDebug;

	protected string $level = self::LEVEL_WARNING;

	public function __construct() {
		$this->isDebug = (bool)Configure::read('debug');
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->passed = true;

		$cacheSize = ini_get('realpath_cache_size');
		$cacheTtl = (int)ini_get('realpath_cache_ttl');

		// Parse cache size to bytes
		$cacheSizeBytes = $this->parseMemorySize($cacheSize ?: '16K');
		$cacheSizeKB = $cacheSizeBytes / 1024;

		$this->infoMessage[] = 'Realpath cache size: ' . $cacheSizeKB . ' KB';
		$this->infoMessage[] = 'Realpath cache TTL: ' . $cacheTtl . ' seconds';

		// Check cache size (recommend 4096K+ for large apps)
		if ($cacheSizeKB < 4096) {
			if (!$this->isDebug) {
				$this->warningMessage[] = 'realpath_cache_size is ' . $cacheSizeKB . ' KB. For large applications, consider 4096K (4M) or higher.';
				$this->passed = false;
			} else {
				$this->infoMessage[] = 'Cache size is below recommended 4096K, but acceptable in development.';
			}
		}

		// Check TTL (recommend 3600+ for production)
		if (!$this->isDebug && $cacheTtl < 3600) {
			$this->warningMessage[] = 'realpath_cache_ttl is ' . $cacheTtl . ' seconds. For production, consider 3600+ seconds (1 hour) for better performance.';
			$this->passed = false;
		}

		// Show current usage statistics
		$this->addUsageStatistics();

		if (!$this->passed) {
			$this->addFixInstructions();
		}
	}

	/**
	 * Add current realpath cache usage statistics.
	 *
	 * @return void
	 */
	protected function addUsageStatistics(): void {
		if (!function_exists('realpath_cache_get')) {
			return;
		}

		$cache = realpath_cache_get();
		$cacheCount = count($cache);

		if ($cacheCount > 0) {
			$this->infoMessage[] = 'Cached paths: ' . $cacheCount;

			$cacheSize = ini_get('realpath_cache_size');
			$cacheSizeBytes = $this->parseMemorySize($cacheSize ?: '16K');

			// Estimate usage (rough calculation)
			$estimatedUsage = $cacheCount * 500; // Rough estimate: 500 bytes per entry
			$usagePercent = ($estimatedUsage / $cacheSizeBytes) * 100;

			if ($usagePercent > 80) {
				$this->warningMessage[] = 'Realpath cache may be near capacity (~' . round($usagePercent) . '% estimated usage). Consider increasing realpath_cache_size.';
			}
		}
	}

	/**
	 * Add helpful information about how to configure realpath cache.
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

		$this->infoMessage[] = 'To optimize realpath cache:';
		$this->infoMessage[] = '1. Increase cache size: realpath_cache_size=4096K (4M for large apps)';
		$this->infoMessage[] = '2. Increase TTL for production: realpath_cache_ttl=3600 (1 hour or more)';

		if ($phpIniPath) {
			$this->infoMessage[] = 'Quick fix via sed:';
			$this->infoMessage[] = '  `sudo sed -i \'s/^;\\?realpath_cache_size.*/realpath_cache_size = 4096K/\' ' . $phpIniPath . '`';
			$this->infoMessage[] = '  `sudo sed -i \'s/^;\\?realpath_cache_ttl.*/realpath_cache_ttl = 3600/\' ' . $phpIniPath . '`';
		}

		$this->infoMessage[] = 'After editing, restart your web server (Apache/Nginx) or PHP-FPM to apply changes.';
		$this->infoMessage[] = 'Impact: Realpath cache reduces file system calls for path resolution, improving performance by 5-15%.';
		$this->infoMessage[] = 'Note: In development, lower values allow for faster file change detection.';
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
