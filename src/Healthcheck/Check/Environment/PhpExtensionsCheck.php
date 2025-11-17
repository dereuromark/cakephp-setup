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
				if (Semver::satisfies(phpversion(), $value)) {
					$extensions[] = $extension;
				}
			}
		}

		return $extensions;
	}

}
