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

	/**
	 * @param string|null $phpVersion
	 * @param string|null $root
	 * @param string|null $overrideComparisonChar
	 */
	public function __construct(?string $phpVersion = null, ?string $root = null, ?string $overrideComparisonChar = null) {
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
				$this->failureMessage[] = 'PHP version `' . $this->phpVersion . '` does not match composer.json requirement `' . $constraint . '`';
				$this->passed = false;
			}
		} elseif (!$this->satisfiesVersionConstraint($constraint, $this->phpVersion)) {
			$this->failureMessage[] = 'PHP version `' . $this->phpVersion . '` does not match composer.json requirement `' . $constraint . '`';
			$this->passed = false;
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
				$this->failureMessage[] = 'PHP version ' . $this->phpVersion . ' does not match platform override requirement: ' . $override;
				$this->passed = false;

				return;
			}
		}

		$result = version_compare($this->phpVersion, $override, '>=');
		if (!$result) {
			$this->failureMessage[] = 'PHP version ' . $this->phpVersion . ' does not match platform override requirement: ' . $override;

			$this->passed = false;
		}
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

}
