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
		}

		$this->infoMessage[] = implode(', ', $loaded);
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
