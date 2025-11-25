<?php

namespace Setup\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class PhpErrorDisplayCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if PHP error display is disabled in production to prevent information leakage.';

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
		$displayErrors = ini_get('display_errors');
		$isEnabled = $displayErrors !== false && $displayErrors !== '' && $displayErrors !== '0' && strtolower($displayErrors) !== 'off';

		if ($this->isDebug) {
			$this->passed = true;
			if ($isEnabled) {
				$this->infoMessage[] = 'display_errors is enabled (acceptable in development).';
			} else {
				$this->infoMessage[] = 'display_errors is disabled.';
			}

			return;
		}

		if ($isEnabled) {
			$this->passed = false;
			$this->warningMessage[] = 'display_errors is enabled in production. This can expose sensitive information (stack traces, file paths, database details) to attackers.';
			$this->infoMessage[] = 'Set display_errors = Off in php.ini or use ini_set(\'display_errors\', \'0\') in bootstrap.';
		} else {
			$this->passed = true;
			$this->infoMessage[] = 'display_errors is disabled in production.';
		}
	}

}
