<?php

namespace Setup\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class DebugModeDisabledCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the debug mode is off in production.';

	protected bool $isDebug;

	protected string $level = self::LEVEL_WARNING;

	public function __construct() {
		$this->isDebug = (bool)Configure::read('debug');
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->passed = !$this->isDebug;

		if (!$this->passed) {
			$this->warningMessage[] = 'Debug mode is enabled. This must be disabled in production environments.';
		}

		$this->infoMessage[] = 'For local development and non-public test servers, debug mode is fine.';
	}

}
