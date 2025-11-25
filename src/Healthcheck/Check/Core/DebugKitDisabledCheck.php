<?php

namespace Setup\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Setup\Healthcheck\Check\Check;

class DebugKitDisabledCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if DebugKit plugin is disabled in production.';

	protected string $level = self::LEVEL_WARNING;

	protected bool $isDebug;

	/**
	 * @var array<string>
	 */
	protected array $scope = [
		self::SCOPE_WEB,
		self::SCOPE_CLI,
	];

	public function __construct() {
		$this->isDebug = (bool)Configure::read('debug');
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$isLoaded = Plugin::isLoaded('DebugKit');

		if ($this->isDebug) {
			$this->passed = true;
			if ($isLoaded) {
				$this->infoMessage[] = 'DebugKit is loaded (acceptable in development).';
			} else {
				$this->infoMessage[] = 'DebugKit is not loaded.';
			}

			return;
		}

		if ($isLoaded) {
			$this->passed = false;
			$this->warningMessage[] = 'DebugKit plugin is loaded in production. This exposes sensitive debugging information and impacts performance.';
			$this->infoMessage[] = 'Remove or conditionally load DebugKit in config/bootstrap.php:';
			$this->infoMessage[] = '  if (Configure::read(\'debug\')) { $this->addPlugin(\'DebugKit\'); }';
		} else {
			$this->passed = true;
			$this->infoMessage[] = 'DebugKit is not loaded in production.';
		}
	}

}
