<?php

namespace Setup\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Check;

class TimezoneCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if a default timezone is explicitly configured.';

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
		$timezone = ini_get('date.timezone');

		if (!$timezone) {
			$this->passed = false;
			$this->warningMessage[] = 'date.timezone is not set. This can cause warnings and inconsistent date/time handling.';
			$this->infoMessage[] = 'Set date.timezone in php.ini (e.g., date.timezone = "UTC") or use date_default_timezone_set() in bootstrap.';

			return;
		}

		// Validate the timezone is recognized
		$validTimezones = timezone_identifiers_list();
		if (!in_array($timezone, $validTimezones, true)) {
			$this->passed = false;
			$this->warningMessage[] = 'date.timezone is set to an unrecognized value: "' . $timezone . '".';
			$this->infoMessage[] = 'Use a valid timezone identifier like "UTC", "America/New_York", or "Europe/London".';

			return;
		}

		$this->passed = true;
		$this->infoMessage[] = 'date.timezone is set to "' . $timezone . '".';
	}

}
