<?php

namespace Setup\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class SessionLifetimeCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the session lifetime is properly configured and not lost after PHP updates.';

	/**
	 * @var int Default minimum session lifetime in minutes
	 */
	protected const DEFAULT_MIN_LIFETIME = 20;

	protected int $minLifetime;

	/**
	 * @var array<string>
	 */
	protected array $scope = [
		self::SCOPE_WEB,
	];

	/**
	 * @param int|null $minLifetime Minimum session lifetime in minutes
	 */
	public function __construct(?int $minLifetime = null) {
		if ($minLifetime === null) {
			$minLifetime = static::DEFAULT_MIN_LIFETIME;
		}

		$this->minLifetime = $minLifetime;
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->assertSessionLifetime();
	}

	/**
	 * @return void
	 */
	protected function assertSessionLifetime(): void {
		$phpGcMaxlifetime = (int)ini_get('session.gc_maxlifetime');
		$phpGcMaxlifetimeMinutes = (int)($phpGcMaxlifetime / 60);

		$cakeSessionTimeout = Configure::read('Session.timeout');

		$this->passed = true;

		// Check if PHP session lifetime is too short
		if ($phpGcMaxlifetime < ($this->minLifetime * 60)) {
			$this->failureMessage[] = 'The PHP session.gc_maxlifetime is too short. It is currently set to `' . $phpGcMaxlifetime . '` seconds (' . $phpGcMaxlifetimeMinutes . ' minutes), but at least ' . $this->minLifetime . ' minutes is recommended.';
			$this->failureMessage[] = 'This setting often gets reset to a low default value after PHP updates.';

			$this->passed = false;
		}

		// Check if CakePHP session timeout is configured
		if ($cakeSessionTimeout !== null) {
			$cakeSessionTimeoutSeconds = $cakeSessionTimeout * 60;

			$this->infoMessage[] = 'CakePHP Session.timeout is set to `' . $cakeSessionTimeout . '` minutes.';

			// Check if PHP lifetime is shorter than CakePHP timeout
			if ($phpGcMaxlifetime < $cakeSessionTimeoutSeconds) {
				$this->warningMessage[] = 'The PHP session.gc_maxlifetime (' . $phpGcMaxlifetimeMinutes . ' minutes) is shorter than CakePHP Session.timeout (' . $cakeSessionTimeout . ' minutes). Sessions may expire earlier than expected in PHP.';

				if ($this->passed) {
					$this->passed = false;
				}
			}
		} else {
			$this->infoMessage[] = 'CakePHP Session.timeout is not explicitly configured, using CakePHP defaults.';
		}
	}

}
