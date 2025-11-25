<?php

namespace Setup\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Check;

class AllowUrlIncludeCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if allow_url_include is disabled to prevent remote file inclusion vulnerabilities.';

	protected string $level = self::LEVEL_WARNING;

	/**
	 * @var array<string>
	 */
	protected array $scope = [
		self::SCOPE_WEB,
		self::SCOPE_CLI,
	];

	/**
	 * @return void
	 */
	public function check(): void {
		$allowUrlInclude = ini_get('allow_url_include');
		$isEnabled = $allowUrlInclude !== false && $allowUrlInclude !== '' && $allowUrlInclude !== '0' && strtolower($allowUrlInclude) !== 'off';

		if ($isEnabled) {
			$this->passed = false;
			$this->warningMessage[] = 'allow_url_include is enabled. This is a serious security risk that can allow remote file inclusion (RFI) attacks.';
			$this->infoMessage[] = 'Set allow_url_include = Off in php.ini. This setting is disabled by default since PHP 5.2.';
		} else {
			$this->passed = true;
			$this->successMessage[] = 'allow_url_include is disabled.';
		}
	}

}
