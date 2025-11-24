<?php

namespace Setup\Healthcheck\Check\Core;

use Setup\Healthcheck\Check\Check;

class FilePermissionsCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if critical directories have appropriate file permissions.';

	protected string $level = self::LEVEL_ERROR;

	/**
	 * @var array<string>
	 */
	protected array $directoriesToCheck = [
		'tmp',
		'logs',
	];

	/**
	 * @param array<string> $directories
	 */
	public function __construct(array $directories = []) {
		if ($directories) {
			$this->directoriesToCheck = $directories;
		}
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->passed = true;

		foreach ($this->directoriesToCheck as $directory) {
			$path = ROOT . DS . $directory;

			// Check if directory exists
			if (!is_dir($path)) {
				$this->failureMessage[] = 'Directory `' . $directory . '` does not exist at: ' . $path;
				$this->passed = false;

				continue;
			}

			// Check if directory is writable
			if (!is_writable($path)) {
				$this->failureMessage[] = 'Directory `' . $directory . '` is not writable: ' . $path;
				$this->passed = false;

				continue;
			}

			// Check permissions
			$perms = fileperms($path);
			$octalPerms = substr(sprintf('%o', $perms), -4);

			$this->infoMessage[] = 'Directory `' . $directory . '` is writable (permissions: ' . $octalPerms . ')';

			// Warn if directory is world-writable (777)
			if (($perms & 0x0002) && ($perms & 0x0001)) {
				$this->warningMessage[] = 'Directory `' . $directory . '` is world-writable (' . $octalPerms . '): `chmod 775 ' . $path . '`';
			}
		}

		// Check config files for security
		$this->checkConfigFilePermissions();

		if (!$this->passed) {
			$this->addFixInstructions();
		}
	}

	/**
	 * Check that config files are not world-writable.
	 *
	 * @return void
	 */
	protected function checkConfigFilePermissions(): void {
		$configFiles = [
			'config' . DS . 'app.php',
			'config' . DS . 'app_local.php',
		];

		foreach ($configFiles as $configFile) {
			$path = ROOT . DS . $configFile;

			if (!is_file($path)) {
				continue;
			}

			$perms = fileperms($path);
			$octalPerms = substr(sprintf('%o', $perms), -4);

			// Check if world-writable (dangerous for config files)
			if ($perms & 0x0002) {
				$this->warningMessage[] = 'Config file `' . $configFile . '` is world-writable (' . $octalPerms . '): `chmod 644 ' . $path . '`';
			}

			// Check if world-readable for sensitive files
			if (str_contains($configFile, 'app_local.php') && ($perms & 0x0004)) {
				$this->infoMessage[] = 'Config file `' . $configFile . '` is world-readable. Consider chmod 640 for sensitive configuration.';
			}
		}
	}

	/**
	 * Add helpful information about how to fix file permissions.
	 *
	 * @return void
	 */
	protected function addFixInstructions(): void {
		$this->infoMessage[] = 'To fix file permissions:';
		$this->infoMessage[] = '1. Ensure directories exist and are writable by the web server user';
		$this->infoMessage[] = '2. Recommended permissions:';
		$this->infoMessage[] = '   - Directories (tmp, logs): chmod 0775 or 0755';
		$this->infoMessage[] = '   - Config files: chmod 0644 (or 0640 for sensitive files)';
		$this->infoMessage[] = '3. Set ownership to web server user (e.g., www-data, apache, nginx)';
		$this->infoMessage[] = 'Example commands:';
		$this->infoMessage[] = '  chmod 0775 tmp/ logs/';
		$this->infoMessage[] = '  chmod 0644 config/app.php';
		$this->infoMessage[] = '  chown -R www-data:www-data tmp/ logs/';
	}

}
