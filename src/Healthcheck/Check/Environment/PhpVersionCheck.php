<?php

namespace Setup\Healthcheck\Check\Environment;

use Composer\Semver\Semver;
use InvalidArgumentException;
use RuntimeException;
use Setup\Healthcheck\Check\Check;

class PhpVersionCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the PHP version in use matches the one in composer json/lock files.';

	/**
	 * @var string
	 */
	protected const OVERRIDE_COMPARISON_CHAR = '~';

	protected string $phpVersion;

	protected string $root;

	protected string $overrideComparisonChar;

	protected string $failOnHigher;

	/**
	 * @param string|null $phpVersion
	 * @param string|null $root
	 * @param string|null $overrideComparisonChar
	 * @param string $failOnHigher 'major' = fail only on major, 'minor' = fail on minor+major (default), 'patch' = fail on any version higher
	 */
	public function __construct(?string $phpVersion = null, ?string $root = null, ?string $overrideComparisonChar = null, string $failOnHigher = 'minor') {
		if ($phpVersion == null) {
			$phpVersion = (string)phpversion();
		}
		if ($root === null) {
			$root = ROOT . DS;
		}
		if (!is_dir($root)) {
			throw new RuntimeException('Cannot find root directory: ' . $root);
		}

		$this->phpVersion = $phpVersion;
		$this->root = $root;
		$this->overrideComparisonChar = $overrideComparisonChar ?? static::OVERRIDE_COMPARISON_CHAR;
		$this->failOnHigher = $failOnHigher;
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->assertVersion();
	}

	/**
	 * @return void
	 */
	protected function assertVersion(): void {
		$this->passed = true;
		$this->checkJsonFile();
		$this->checkLockFile();

		if ($this->passed()) {
			$this->infoMessage[] = 'The PHP version is `' . $this->phpVersion . '`';
		}
	}

	/**
	 * @return void
	 */
	protected function checkJsonFile(): void {
		$composerFile = $this->root . 'composer.json';
		if (!is_file($composerFile)) {
			throw new RuntimeException('Cannot find composer.json file: ' . $composerFile);
		}

		$composer = json_decode((string)file_get_contents($composerFile), true);
		$constraint = $composer['require']['php'] ?? null;
		if (!$constraint) {
			$this->warningMessage[] = 'No PHP version requirement found in composer.json.';

			$this->passed = false;
		}

		if (class_exists(Semver::class)) {
			if (!Semver::satisfies($this->phpVersion, $constraint)) {
				$this->handleVersionMismatch($constraint);
			} else {
				$this->checkHigherVersion($constraint);
			}
		} elseif (!$this->satisfiesVersionConstraint($constraint, $this->phpVersion)) {
			$this->handleVersionMismatch($constraint);
		} else {
			$this->checkHigherVersion($constraint);
		}
	}

	/**
	 * @return void
	 */
	protected function checkLockFile(): void {
		$lockFile = $this->root . 'composer.lock';
		if (!is_file($lockFile)) {
			$this->warningMessage[] = 'You need to create the lock file first: ' . $lockFile;
			$this->passed = false;

			return;
		}

		$content = json_decode((string)file_get_contents($lockFile), true);
		$override = $content['platform-overrides']['php'] ?? null;
		if (!$override) {
			return;
		}

		if (class_exists(Semver::class)) {
			$result = Semver::satisfies($this->phpVersion, $this->overrideComparisonChar . $override);
			if (!$result) {
				$this->handleVersionMismatch($this->overrideComparisonChar . $override, 'platform override');

				return;
			}
		}

		$result = version_compare($this->phpVersion, $override, '>=');
		if (!$result) {
			$this->handleVersionMismatch($this->overrideComparisonChar . $override, 'platform override');
		}
	}

	/**
	 * Handle version mismatch based on the difference level
	 *
	 * @param string $constraint
	 * @param string $source
	 * @return void
	 */
	protected function handleVersionMismatch(string $constraint, string $source = 'composer.json'): void {
		$message = 'PHP version `' . $this->phpVersion . '` does not match ' . $source . ' requirement `' . $constraint . '`';
		$diff = $this->getVersionDifference($this->phpVersion, $constraint);

		// If version is too low, always fail with error
		if ($diff === 'lower') {
			$this->failureMessage[] = $message;
			$this->passed = false;
			$this->level = static::LEVEL_ERROR;

			return;
		}

		// Handle higher versions based on configuration
		if ($diff === 'major') {
			// Always fail on major version difference with error
			$this->failureMessage[] = $message;
			$this->passed = false;
			$this->level = static::LEVEL_ERROR;
		} elseif ($diff === 'minor') {
			if ($this->failOnHigher === 'minor' || $this->failOnHigher === 'patch') {
				$this->failureMessage[] = $message;
				$this->passed = false;
				$this->level = static::LEVEL_ERROR;
			} else {
				// When configured to only fail on major, fail with warning level for minor differences
				$this->warningMessage[] = 'PHP version `' . $this->phpVersion . '` is higher (minor) than ' . $source . ' requirement `' . $constraint . '`';
				$this->passed = false;
				$this->level = static::LEVEL_WARNING;
			}
		} elseif ($diff === 'patch') {
			if ($this->failOnHigher === 'patch') {
				$this->failureMessage[] = $message;
				$this->passed = false;
				$this->level = static::LEVEL_ERROR;
			}
			// For patch differences when not strict, pass silently
		}
	}

	/**
	 * Check if we should warn about higher versions even when constraint is satisfied
	 *
	 * @param string $constraint
	 * @return void
	 */
	protected function checkHigherVersion(string $constraint): void {
		$diff = $this->getVersionDifference($this->phpVersion, $constraint);

		if ($diff === 'minor' && $this->failOnHigher === 'major') {
			// When configured to only fail on major, fail with warning level for minor differences
			$this->warningMessage[] = 'PHP version `' . $this->phpVersion . '` is higher (minor version) than base constraint `' . $constraint . '`';
			$this->passed = false;
			$this->level = static::LEVEL_WARNING;
		}
		// Don't show anything for patch differences or when already handling in handleVersionMismatch
	}

	/**
	 * @param string $constraint
	 * @param string $version
	 * @return bool
	 */
	protected function satisfiesVersionConstraint(string $constraint, string $version): bool {
		// Match operator and version using regex
		if (preg_match('/^(>=|<=|==|!=|>|<|=)?\s*([\d\.]+)$/', $constraint, $matches)) {
			$operator = $matches[1] ?: '==';
			$targetVersion = $matches[2];

			return version_compare($version, $targetVersion, $operator);
		}

		throw new InvalidArgumentException("Invalid version constraint `$constraint`. Install composer/semver or use a default constraint format.");
	}

	/**
	 * Determine the difference level between the current version and constraint
	 *
	 * @param string $version
	 * @param string $constraint
	 * @return string|null 'major', 'minor', 'patch', 'lower', or null if within range
	 */
	protected function getVersionDifference(string $version, string $constraint): ?string {
		// Extract version parts
		$versionParts = explode('.', $version);
		$currentMajor = (int)$versionParts[0];
		$currentMinor = (int)($versionParts[1] ?? 0);
		$currentPatch = (int)($versionParts[2] ?? 0);

		// Extract base version from constraint
		if (!preg_match('/^(>=?|<=?|==?|!=|~|\^)?\s*([\d\.]+)/', $constraint, $matches)) {
			return null;
		}

		$operator = $matches[1] ?: '==';
		$baseVersion = $matches[2];
		$baseParts = explode('.', $baseVersion);
		$baseMajor = (int)$baseParts[0];
		$baseMinor = (int)($baseParts[1] ?? 0);
		$basePatch = (int)($baseParts[2] ?? 0);

		// Check if version is lower than minimum requirement
		if ($operator === '>=' || $operator === '>') {
			if (version_compare($version, $baseVersion, '<')) {
				return 'lower';
			}
		}

		// For caret (^) operator: ^8.1 means >=8.1.0 <9.0.0
		if ($operator === '^') {
			if ($currentMajor < $baseMajor) {
				return 'lower';
			}
			if ($currentMajor > $baseMajor) {
				return 'major';
			}
			if ($currentMajor === $baseMajor) {
				if ($currentMinor < $baseMinor) {
					return 'lower';
				}
				if ($currentMinor > $baseMinor) {
					return 'minor';
				}
				if ($currentPatch > $basePatch) {
					return 'patch';
				}
			}

			return null;
		}

		// For tilde (~) operator: ~8.3.1 means >=8.3.1 <8.4.0
		if ($operator === '~') {
			// First check if we're below the minimum version
			if (version_compare($version, $baseVersion, '<')) {
				return 'lower';
			}
			// Check if we exceed the allowed range
			if ($currentMajor > $baseMajor) {
				return 'major';
			}
			if ($currentMajor === $baseMajor && $currentMinor > $baseMinor) {
				return 'minor';
			}
			// Within allowed range but higher patch
			if ($currentPatch > $basePatch) {
				return 'patch';
			}

			return null;
		}

		// For >= operator, check how much higher we are
		if ($operator === '>=') {
			if (version_compare($version, $baseVersion, '<')) {
				return 'lower';
			}
			if ($currentMajor > $baseMajor) {
				return 'major';
			}
			if ($currentMinor > $baseMinor) {
				return 'minor';
			}
			if ($currentPatch > $basePatch) {
				return 'patch';
			}

			return null;
		}

		// For < or <= operators
		if ($operator === '<' || $operator === '<=') {
			$cmp = version_compare($version, $baseVersion);
			if (($operator === '<' && $cmp >= 0) || ($operator === '<=' && $cmp > 0)) {
				// We're higher than the maximum allowed
				if ($currentMajor > $baseMajor) {
					return 'major';
				}
				if ($currentMinor > $baseMinor) {
					return 'minor';
				}

				return 'patch';
			}

			return null;
		}

		// For exact version match
		if ($operator === '=' || $operator === '==') {
			$cmp = version_compare($version, $baseVersion);
			if ($cmp < 0) {
				return 'lower';
			}
			if ($cmp > 0) {
				if ($currentMajor > $baseMajor) {
					return 'major';
				}
				if ($currentMinor > $baseMinor) {
					return 'minor';
				}

				return 'patch';
			}
		}

		return null;
	}

}
