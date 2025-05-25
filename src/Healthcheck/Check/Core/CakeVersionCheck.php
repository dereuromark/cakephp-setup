<?php

namespace Setup\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Composer\Semver\Semver;
use RuntimeException;
use Setup\Healthcheck\Check\Check;

class CakeVersionCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the CakePHP version in use matches the one in lock file.';

	protected string $cakeVersion;

	protected string $root;

	/**
	 * @param string|null $cakeVersion
	 * @param string|null $root
	 */
	public function __construct(?string $cakeVersion = null, ?string $root = null) {
		if ($cakeVersion == null) {
			$cakeVersion = Configure::version();
		}
		if ($root === null) {
			$root = ROOT . DS;
		}
		if (!is_dir($root)) {
			throw new RuntimeException('Cannot find root directory: ' . $root);
		}

		$this->cakeVersion = $cakeVersion;
		$this->root = $root;
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->passed = $this->assertVersion();
	}

	/**
	 * @return bool
	 */
	protected function assertVersion(): bool {
		$lockFile = $this->root . 'composer.lock';
		if (!is_file($lockFile)) {
			$this->warningMessage[] = 'You need to create the lock file first using composer install: ' . $lockFile;

			return false;
		}

		$content = json_decode((string)file_get_contents($lockFile), true);
		$packages = Hash::combine($content['packages'], '{n}.name', '{n}.version');
		$version = $packages['cakephp/cakephp'] ?? null;
		if (!$version) {
			$this->warningMessage[] = 'CakePHP does not seem installed as require dependency.';

			return false;
		}

		if (str_starts_with($version, 'dev-') || str_ends_with($version, '-dev')) {
			$this->infoMessage[] = 'CakePHP is installed as dev version `' . $version . '`, which is not recommended for production.';

			return true;
		}

		if (class_exists(Semver::class)) {
			if (!Semver::satisfies($this->cakeVersion, $version)) {
				$this->failureMessage[] = 'Installed CakePHP version `' . $this->cakeVersion . '` does not match lock file version `' . $version . '`';

				return false;
			}

			$this->infoMessage[] = $this->cakeVersion;

			return true;
		}

		$result = $this->cakeVersion === $version;
		if (!$result) {
			$this->failureMessage[] = 'PHP version `' . $this->cakeVersion . '` does not match lock file version `' . $version . '`';

			return false;
		}

		$this->infoMessage[] = $this->cakeVersion;

		return true;
	}

}
