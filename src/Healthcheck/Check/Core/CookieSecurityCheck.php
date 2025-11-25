<?php

namespace Setup\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class CookieSecurityCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if session cookies are configured with security best practices (HttpOnly, Secure, SameSite).';

	protected string $level = self::LEVEL_WARNING;

	protected bool $isDebug;

	/**
	 * @var array<string>
	 */
	protected array $scope = [
		self::SCOPE_WEB,
	];

	public function __construct() {
		$this->isDebug = (bool)Configure::read('debug');
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->passed = true;

		$this->checkHttpOnly();
		$this->checkSecure();
		$this->checkSameSite();
		$this->checkStrictMode();
	}

	/**
	 * Check if session.cookie_httponly is enabled.
	 *
	 * @return void
	 */
	protected function checkHttpOnly(): void {
		$httpOnly = ini_get('session.cookie_httponly');

		if ($httpOnly === false || $httpOnly === '' || $httpOnly === '0') {
			$this->warningMessage[] = 'session.cookie_httponly is disabled. Enable it to prevent JavaScript access to session cookies (XSS mitigation).';
			$this->infoMessage[] = 'Set in php.ini: session.cookie_httponly = 1 or in CakePHP config: \'Session\' => [\'ini\' => [\'session.cookie_httponly\' => true]]';
			$this->passed = false;
		} else {
			$this->infoMessage[] = 'session.cookie_httponly is enabled.';
		}
	}

	/**
	 * Check if session.cookie_secure is enabled.
	 *
	 * @return void
	 */
	protected function checkSecure(): void {
		$secure = ini_get('session.cookie_secure');
		$isEnabled = $secure !== false && $secure !== '' && $secure !== '0';

		// In debug mode, secure cookies are optional (local dev often uses HTTP)
		if ($this->isDebug) {
			if ($isEnabled) {
				$this->infoMessage[] = 'session.cookie_secure is enabled.';
			} else {
				$this->infoMessage[] = 'session.cookie_secure is disabled (acceptable in development).';
			}

			return;
		}

		if (!$isEnabled) {
			$this->warningMessage[] = 'session.cookie_secure is disabled. Enable it in production to ensure cookies are only sent over HTTPS.';
			$this->infoMessage[] = 'Set in php.ini: session.cookie_secure = 1 or in CakePHP config: \'Session\' => [\'ini\' => [\'session.cookie_secure\' => true]]';
			$this->passed = false;
		} else {
			$this->infoMessage[] = 'session.cookie_secure is enabled.';
		}
	}

	/**
	 * Check if session.cookie_samesite is configured.
	 *
	 * @return void
	 */
	protected function checkSameSite(): void {
		$sameSite = ini_get('session.cookie_samesite');

		if (!$sameSite) {
			$this->warningMessage[] = 'session.cookie_samesite is not set. Consider setting to "Lax" or "Strict" for CSRF protection.';
			$this->infoMessage[] = 'Set in php.ini: session.cookie_samesite = "Lax" or in CakePHP config: \'Session\' => [\'ini\' => [\'session.cookie_samesite\' => \'Lax\']]';
			$this->passed = false;

			return;
		}

		$sameSite = ucfirst(strtolower($sameSite));

		if ($sameSite === 'None') {
			if ($this->isDebug) {
				$this->infoMessage[] = 'session.cookie_samesite is set to "None" (acceptable in development).';
			} else {
				$this->warningMessage[] = 'session.cookie_samesite is set to "None". Consider using "Lax" or "Strict" unless cross-site requests are required.';
				$this->passed = false;
			}
		} elseif ($sameSite === 'Strict') {
			$this->infoMessage[] = 'session.cookie_samesite is set to "Strict" (maximum CSRF protection).';
		} elseif ($sameSite === 'Lax') {
			$this->infoMessage[] = 'session.cookie_samesite is set to "Lax" (good balance of security and usability).';
		} else {
			$this->warningMessage[] = 'session.cookie_samesite has an unrecognized value: "' . $sameSite . '". Use "Strict", "Lax", or "None".';
			$this->passed = false;
		}
	}

	/**
	 * Check if session.use_strict_mode is enabled.
	 *
	 * @return void
	 */
	protected function checkStrictMode(): void {
		$strictMode = ini_get('session.use_strict_mode');

		if ($strictMode === false || $strictMode === '' || $strictMode === '0') {
			$this->warningMessage[] = 'session.use_strict_mode is disabled. Enable it to reject uninitialized session IDs (session fixation mitigation).';
			$this->infoMessage[] = 'Set in php.ini: session.use_strict_mode = 1 or in CakePHP config: \'Session\' => [\'ini\' => [\'session.use_strict_mode\' => true]]';
			$this->passed = false;
		} else {
			$this->infoMessage[] = 'session.use_strict_mode is enabled.';
		}
	}

}
