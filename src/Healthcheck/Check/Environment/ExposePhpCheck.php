<?php

namespace Setup\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class ExposePhpCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if PHP version exposure is disabled in HTTP headers.';

	protected string $level = self::LEVEL_INFO;

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
		if ($this->isDebug) {
			$this->passed = true;
			$this->infoMessage[] = 'expose_php check skipped in development mode.';

			return;
		}

		$exposePhp = ini_get('expose_php');
		$isEnabled = $exposePhp !== false && $exposePhp !== '' && $exposePhp !== '0' && strtolower($exposePhp) !== 'off';

		if ($isEnabled) {
			$this->passed = true;
			$this->infoMessage[] = 'expose_php is enabled. PHP version is exposed in X-Powered-By header. Consider disabling to reduce information disclosure.';
			$this->infoMessage[] = 'Set expose_php = Off in php.ini (requires restart, cannot be changed at runtime).';
		} else {
			$this->passed = true;
			$this->successMessage[] = 'expose_php is disabled.';
		}
	}

}
