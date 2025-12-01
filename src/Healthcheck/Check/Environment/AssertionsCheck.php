<?php

namespace Setup\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class AssertionsCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if PHP assertions are configured correctly for the environment.';

	protected bool $isDebug;

	protected int $zendAssertions;

	protected bool $assertActive;

	protected string $level = self::LEVEL_INFO;

	public function __construct() {
		$this->isDebug = (bool)Configure::read('debug');
		$this->zendAssertions = (int)ini_get('zend.assertions');
		$this->assertActive = (bool)ini_get('assert.active');
	}

	/**
	 * @return void
	 */
	public function check(): void {
		// zend.assertions: -1 = removed at compile time, 0 = not executed, 1 = executed
		// assert.active: 0 = disabled, 1 = enabled
		$assertionsEnabled = $this->zendAssertions === 1 && $this->assertActive;
		$assertionsDisabled = $this->zendAssertions <= 0 && !$this->assertActive;

		if ($this->isDebug) {
			// In development mode, assertions should be enabled
			$this->passed = $assertionsEnabled;

			if ($this->passed) {
				$this->infoMessage[] = 'zend.assertions = ' . $this->zendAssertions . ', assert.active = ' . ($this->assertActive ? '1' : '0');
			} else {
				$this->warningMessage[] = 'PHP assertions are disabled in development mode. Enable them for better debugging.';
				$this->addEnableInstructions();
			}

			return;
		}

		// In production mode (debug off), assertions should be disabled
		$this->passed = $assertionsDisabled;

		if ($this->passed) {
			$this->infoMessage[] = 'zend.assertions = ' . $this->zendAssertions . ', assert.active = ' . ($this->assertActive ? '1' : '0');
		} else {
			$this->infoMessage[] = 'PHP assertions are enabled in production mode. Consider disabling for minor performance improvement.';
			$this->addDisableInstructions();
		}
	}

	/**
	 * Add instructions for enabling assertions.
	 *
	 * @return void
	 */
	protected function addEnableInstructions(): void {
		$phpIniPath = php_ini_loaded_file();

		$this->infoMessage[] = 'Current settings:';
		$this->infoMessage[] = '  zend.assertions = ' . $this->zendAssertions . ' (recommended: 1)';
		$this->infoMessage[] = '  assert.active = ' . ($this->assertActive ? '1' : '0') . ' (recommended: 1)';

		if ($phpIniPath) {
			$this->infoMessage[] = 'Quick fix for ' . $phpIniPath . ':';
			$this->infoMessage[] = '  `sudo sed -i \'s/^;\\?zend.assertions.*/zend.assertions = 1/\' ' . $phpIniPath . '`';
			$this->infoMessage[] = '  `sudo sed -i \'s/^;\\?assert.active.*/assert.active = 1/\' ' . $phpIniPath . '`';
		}

		$this->infoMessage[] = 'After editing php.ini, restart PHP-FPM: `sudo systemctl restart php-fpm` or Apache: `sudo systemctl restart apache2`';
	}

	/**
	 * Add instructions for disabling assertions.
	 *
	 * @return void
	 */
	protected function addDisableInstructions(): void {
		$phpIniPath = php_ini_loaded_file();

		$this->infoMessage[] = 'Current settings:';
		$this->infoMessage[] = '  zend.assertions = ' . $this->zendAssertions . ' (recommended: -1)';
		$this->infoMessage[] = '  assert.active = ' . ($this->assertActive ? '1' : '0') . ' (recommended: 0)';

		if ($phpIniPath) {
			$this->infoMessage[] = 'Quick fix for ' . $phpIniPath . ':';
			$this->infoMessage[] = '  `sudo sed -i \'s/^;\\?zend.assertions.*/zend.assertions = -1/\' ' . $phpIniPath . '`';
			$this->infoMessage[] = '  `sudo sed -i \'s/^;\\?assert.active.*/assert.active = 0/\' ' . $phpIniPath . '`';
		}

		$this->infoMessage[] = 'Note: zend.assertions can only be set in php.ini, not at runtime.';
		$this->infoMessage[] = 'After editing php.ini, restart PHP-FPM: `sudo systemctl restart php-fpm` or Apache: `sudo systemctl restart apache2`';
	}

}
