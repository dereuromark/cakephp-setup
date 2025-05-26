<?php

namespace Setup\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class CakeSaltCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the CakePHP salt is set up.';

	/**
	 * @return void
	 */
	public function check(): void {
		$this->passed = $this->assertSalt();
	}

	/**
	 * @return string[]
	 */
	public function failureMessage(): array {
		return [
			'Security.salt is not set up yet.',
		];
	}

	/**
	 * @return bool
	 */
	protected function assertSalt(): bool {
		$salt = Configure::read('Security.salt');

		return $salt !== '__SALT__';
	}

}
