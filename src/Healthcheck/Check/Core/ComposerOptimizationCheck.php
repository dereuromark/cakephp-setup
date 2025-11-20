<?php

namespace Setup\Healthcheck\Check\Core;

use Cake\Core\Configure;
use RuntimeException;
use Setup\Healthcheck\Check\Check;

class ComposerOptimizationCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if Composer autoloader is optimized for production.';

	protected bool $isDebug;

	protected string $level = self::LEVEL_WARNING;

	public function __construct() {
		$this->isDebug = (bool)Configure::read('debug');
	}

	/**
	 * @return void
	 */
	public function check(): void {
		// In development mode, optimization is not critical
		if ($this->isDebug) {
			$this->passed = true;
			$this->infoMessage[] = 'Composer autoloader optimization is optional in development mode.';

			return;
		}

		$this->passed = true;

		// Check for vendor directory
		$vendorDir = ROOT . DS . 'vendor';
		if (!is_dir($vendorDir)) {
			throw new RuntimeException('Vendor directory not found at ' . $vendorDir);
		}

		// Check for optimized classmap
		$classmapFile = $vendorDir . DS . 'composer' . DS . 'autoload_classmap.php';
		if (!is_file($classmapFile)) {
			$this->warningMessage[] = 'Composer classmap file not found. Run `composer dump-autoload --optimize` for production.';
			$this->passed = false;
			$this->addFixInstructions();

			return;
		}

		// Check classmap size (should have substantial entries if optimized)
		$classmap = include $classmapFile;
		if (!is_array($classmap)) {
			$this->warningMessage[] = 'Composer classmap is invalid.';
			$this->passed = false;
			$this->addFixInstructions();

			return;
		}

		$classmapCount = count($classmap);
		$this->infoMessage[] = 'Composer classmap entries: ' . $classmapCount;

		// If classmap is very small, autoloader might not be optimized
		if ($classmapCount < 100) {
			$this->warningMessage[] = 'Composer classmap has only ' . $classmapCount . ' entries. The autoloader may not be optimized.';
			$this->warningMessage[] = 'Run `composer dump-autoload --optimize` or `composer install --optimize-autoloader` for production.';
			$this->passed = false;
		}

		// Check for APCu optimization (level 2)
		$autoloadRealFile = $vendorDir . DS . 'autoload.php';
		if (is_file($autoloadRealFile)) {
			$autoloadContent = file_get_contents($autoloadRealFile);
			if ($autoloadContent && str_contains($autoloadContent, 'APCu')) {
				$this->infoMessage[] = 'APCu autoloader optimization detected (level 2 optimization).';
			} else {
				$this->infoMessage[] = 'For maximum performance, consider `composer dump-autoload --optimize --apcu` if APCu is available.';
			}
		}

		if (!$this->passed) {
			$this->addFixInstructions();
		}
	}

	/**
	 * Add helpful information about how to optimize Composer autoloader.
	 *
	 * @return void
	 */
	protected function addFixInstructions(): void {
		$this->infoMessage[] = 'To optimize Composer autoloader for production:';
		$this->infoMessage[] = '1. Basic optimization: composer dump-autoload --optimize';
		$this->infoMessage[] = '2. Or during install: composer install --optimize-autoloader --no-dev';
		$this->infoMessage[] = '3. For APCu optimization: composer dump-autoload --optimize --apcu (requires APCu extension)';
		$this->infoMessage[] = 'Benefits:';
		$this->infoMessage[] = '  - Converts PSR-4/PSR-0 rules into classmap for faster autoloading';
		$this->infoMessage[] = '  - Reduces file system lookups';
		$this->infoMessage[] = '  - Can improve performance by 5-20% depending on application';
		$this->infoMessage[] = 'Note: Run this as part of your deployment process.';
	}

}
