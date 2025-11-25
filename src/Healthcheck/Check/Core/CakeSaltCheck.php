<?php

namespace Setup\Healthcheck\Check\Core;

use Cake\Utility\Security;
use Setup\Healthcheck\Check\Check;

class CakeSaltCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the CakePHP salt is set up and sufficiently long.';

	/**
	 * @var int Minimum recommended salt length
	 */
	protected const MIN_SALT_LENGTH = 32;

	/**
	 * @return void
	 */
	public function check(): void {
		$salt = Security::getSalt();

		if ($salt === '' || $salt === '__SALT__') {
			$this->passed = false;
			$this->failureMessage[] = 'Security.salt is not set up yet.';

			return;
		}

		$length = strlen($salt);

		if ($length < static::MIN_SALT_LENGTH) {
			$this->passed = false;
			$this->warningMessage[] = 'Security.salt is only ' . $length . ' characters. A minimum of ' . static::MIN_SALT_LENGTH . ' characters is recommended for strong cryptographic security.';
			$this->infoMessage[] = 'Generate a new salt using a random string generator with at least ' . static::MIN_SALT_LENGTH . ' characters.';

			return;
		}

		$this->passed = true;
		$this->infoMessage[] = 'Security.salt is configured (' . $length . ' characters).';
	}

}
