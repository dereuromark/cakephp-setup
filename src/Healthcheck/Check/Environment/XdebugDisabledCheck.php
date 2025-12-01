<?php

namespace Setup\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class XdebugDisabledCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if Xdebug is disabled in production mode (when debug is off).';

	protected bool $isDebug;

	protected bool $xdebugEnabled;

	protected string $level = self::LEVEL_WARNING;

	public function __construct() {
		$this->isDebug = (bool)Configure::read('debug');
		$this->xdebugEnabled = extension_loaded('xdebug');
	}

	/**
	 * @return void
	 */
	public function check(): void {
		// If debug mode is on (development), xdebug is fine
		if ($this->isDebug) {
			$this->passed = true;
			if ($this->xdebugEnabled) {
				$this->infoMessage[] = 'Xdebug is enabled, which is acceptable in development mode.';
			}

			return;
		}

		// In production mode (debug off), xdebug should be disabled
		$this->passed = !$this->xdebugEnabled;

		if (!$this->passed) {
			$this->warningMessage[] = 'Xdebug is enabled in production mode. This significantly impacts performance and should be disabled.';
			$this->addFixInstructions();
		}
	}

	/**
	 * Add helpful information about how to disable Xdebug.
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
				$this->infoMessage[] = 'Check for xdebug configuration in these files as well.';
			}
		} else {
			$this->infoMessage[] = 'PHP configuration file location not found. Run `php --ini` to locate your php.ini file.';
		}

		$this->infoMessage[] = 'To disable Xdebug:';
		$this->infoMessage[] = '1. Comment out or remove the xdebug extension line in your php.ini or conf.d/*.ini files';
		$this->infoMessage[] = '   Example: Change `zend_extension=xdebug.so` to `;zend_extension=xdebug.so`';
		$this->infoMessage[] = '2. Or disable xdebug mode: xdebug.mode=off';

		if ($phpIniPath) {
			$this->infoMessage[] = 'Quick fix: `sudo sed -i \'s/^;\\?xdebug.mode.*/xdebug.mode = off/\' ' . $phpIniPath . '`';
		}

		$this->infoMessage[] = '3. After editing, restart your web server (Apache/Nginx) or PHP-FPM to apply changes.';
		$this->infoMessage[] = 'Performance impact: Xdebug can slow down PHP execution by 2-10x even when not actively debugging.';
	}

}
