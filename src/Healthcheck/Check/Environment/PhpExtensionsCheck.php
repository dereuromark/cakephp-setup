<?php

namespace Setup\Healthcheck\Check\Environment;

use Composer\Semver\Semver;
use InvalidArgumentException;
use RuntimeException;
use Setup\Healthcheck\Check\Check;

class PhpExtensionsCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the required PHP extensions are installed.';

	/**
	 * @var array<string>
	 */
	protected const DEFAULT_EXTENSIONS = [
		'intl',
		'mbstring',
		'json',
	];

	/**
	 * @var array<string>
	 */
	protected array $extensions = [];

	/**
	 * @param array $extensions
	 * @param bool $includeComposerDefinition
	 */
	public function __construct(array $extensions = [], bool $includeComposerDefinition = true) {
		if (!$extensions) {
			$extensions = static::DEFAULT_EXTENSIONS;
		}
		if ($includeComposerDefinition) {
			$extensions = array_merge($extensions, $this->composerExtensions());
			$extensions = array_unique($extensions);
		}

		$this->extensions = $extensions;
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->assertExtensions();
	}

	/**
	 * @return void
	 */
	protected function assertExtensions(): void {
		$this->passed = true;

		$loaded = get_loaded_extensions();
		$missing = [];
		foreach ($this->extensions as $extension) {
			if (!in_array($extension, $loaded, true)) {
				$missing[] = $extension;
			}
		}

		if ($missing) {
			$this->passed = false;
			$this->failureMessage[] = 'PHP extensions missing: ' . implode(', ', $missing);
			$this->addFixInstructions($missing);
		}

		$this->infoMessage[] = 'Loaded extensions: ' . implode(', ', $loaded);
	}

	/**
	 * Add helpful information about how to install missing extensions.
	 *
	 * @param array<string> $missing Missing extensions
	 * @return void
	 */
	protected function addFixInstructions(array $missing): void {
		$phpVersion = 'php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

		$this->infoMessage[] = 'To install missing PHP extensions:';

		// Debian/Ubuntu
		$debianPackages = array_map(fn ($ext) => $phpVersion . '-' . $ext, $missing);
		$this->infoMessage[] = 'Debian/Ubuntu: sudo apt-get install ' . implode(' ', $debianPackages);

		// RHEL/CentOS/Fedora
		$rhelPackages = array_map(fn ($ext) => $phpVersion . '-' . $ext, $missing);
		$this->infoMessage[] = 'RHEL/CentOS/Fedora: sudo yum install ' . implode(' ', $rhelPackages);

		// macOS
		$this->infoMessage[] = 'macOS: brew install ' . implode(' ', array_map(fn ($ext) => 'php-' . $ext, $missing));

		// PECL
		$this->infoMessage[] = 'Or via PECL: sudo pecl install ' . implode(' ', $missing);

		$this->infoMessage[] = 'After installation, restart your web server (Apache/Nginx) or PHP-FPM.';
	}

	/**
	 * @return array<string>
	 */
	protected function composerExtensions(): array {
		$file = ROOT . DS . 'composer.json';
		if (!is_file($file)) {
			throw new RuntimeException('Cannot find composer.json file at ' . $file);
		}

		$composer = json_decode((string)file_get_contents($file), true);
		if (!is_array($composer) || !isset($composer['require']) || !is_array($composer['require'])) {
			throw new InvalidArgumentException('Invalid composer.json file at ' . $file);
		}

		$extensions = [];
		foreach ($composer['require'] as $key => $value) {
			if (str_starts_with($key, 'ext-')) {
				$extension = substr($key, 4);
				if ($this->satisfiesVersion($value)) {
					$extensions[] = $extension;
				}
			}
		}

		return $extensions;
	}

	/**
	 * Check if the current PHP version satisfies the version constraint.
	 *
	 * @param string $constraint Version constraint from composer.json
	 * @return bool
	 */
	protected function satisfiesVersion(string $constraint): bool {
		if (class_exists(Semver::class)) {
			return Semver::satisfies(phpversion(), $constraint);
		}

		// Fallback: simple version comparison for common cases
		$phpVersion = phpversion();

		// Handle wildcard "*"
		if ($constraint === '*') {
			return true;
		}

		// Handle simple version constraints like "^8.0", ">=7.4", "~8.1"
		// Remove spaces
		$constraint = str_replace(' ', '', $constraint);

		// Handle "^" (caret) operator - allows updates that do not modify the left-most non-zero digit
		if (str_starts_with($constraint, '^')) {
			$minVersion = substr($constraint, 1);
			$parts = explode('.', $minVersion);

			// Caret allows changes that do not modify the left-most non-zero element
			// ^1.2.3 means >=1.2.3 <2.0.0
			// ^0.2.3 means >=0.2.3 <0.3.0
			// ^0.0.3 means >=0.0.3 <0.0.4
			if (!version_compare($phpVersion, $minVersion, '>=')) {
				return false;
			}

			// Determine the upper bound based on the left-most non-zero digit
			if ((int)$parts[0] > 0) {
				// Major version is non-zero: increment major version
				$maxVersion = ((int)$parts[0] + 1) . '.0.0';
			} elseif (isset($parts[1]) && (int)$parts[1] > 0) {
				// Major is 0, minor is non-zero: increment minor version
				$maxVersion = '0.' . ((int)$parts[1] + 1) . '.0';
			} else {
				// Major and minor are 0: increment patch version
				$patchVersion = isset($parts[2]) ? (int)$parts[2] : 0;
				$maxVersion = '0.0.' . ($patchVersion + 1);
			}

			return version_compare($phpVersion, $maxVersion, '<');
		}

		// Handle "~" (tilde) operator - allows patch-level changes
		if (str_starts_with($constraint, '~')) {
			$minVersion = substr($constraint, 1);
			$parts = explode('.', $minVersion);

			// Tilde allows patch-level changes
			// ~1.2.3 means >=1.2.3 <1.3.0
			// ~1.2 means >=1.2.0 <2.0.0
			if (!version_compare($phpVersion, $minVersion, '>=')) {
				return false;
			}

			// If only major.minor specified, increment major
			if (count($parts) === 2) {
				$maxVersion = ((int)$parts[0] + 1) . '.0.0';
			} else {
				// If major.minor.patch specified, increment minor
				$maxVersion = $parts[0] . '.' . ((int)$parts[1] + 1) . '.0';
			}

			return version_compare($phpVersion, $maxVersion, '<');
		}

		// Handle comparison operators (>=, <=, >, <, !=)
		if (preg_match('/^([><=!]+)(.+)$/', $constraint, $matches)) {
			$operator = $matches[1];
			$version = $matches[2];

			return version_compare($phpVersion, $version, $operator);
		}

		// Exact version match
		return version_compare($phpVersion, $constraint, '>=');
	}

}
