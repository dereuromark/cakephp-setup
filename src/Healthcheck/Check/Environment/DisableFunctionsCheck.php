<?php

namespace Setup\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class DisableFunctionsCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if dangerous PHP functions are disabled in production mode.';

	/**
	 * Functions that are commonly disabled for security.
	 *
	 * @var array<string>
	 */
	protected const DANGEROUS_FUNCTIONS = [
		'exec',
		'shell_exec',
		'system',
		'passthru',
		'popen',
		'proc_open',
	];

	protected bool $isDebug;

	/**
	 * @var array<string>
	 */
	protected array $disabledFunctions;

	protected string $level = self::LEVEL_INFO;

	public function __construct() {
		$this->isDebug = (bool)Configure::read('debug');
		$disabledStr = ini_get('disable_functions') ?: '';
		$this->disabledFunctions = array_map('trim', explode(',', $disabledStr));
		$this->disabledFunctions = array_filter($this->disabledFunctions);
	}

	/**
	 * @return void
	 */
	public function check(): void {
		// In debug mode, skip this check (developers may need these functions)
		if ($this->isDebug) {
			$this->passed = true;

			return;
		}

		$enabledDangerous = $this->getEnabledDangerousFunctions();

		$this->passed = count($enabledDangerous) === 0;

		$disabledDangerous = array_diff(static::DANGEROUS_FUNCTIONS, $enabledDangerous);

		if (!$this->passed) {
			$this->infoMessage[] = 'Dangerous functions enabled: ' . implode(', ', $enabledDangerous);
			$this->infoMessage[] = 'Consider disabling in php.ini: disable_functions = ' . implode(',', $enabledDangerous);
			$this->infoMessage[] = 'Note: Some apps (Composer, deployment scripts) may require these.';
		} else {
			$this->infoMessage[] = 'Disabled: ' . implode(', ', $disabledDangerous);
		}
	}

	/**
	 * Get list of dangerous functions that are currently enabled.
	 *
	 * @return array<string>
	 */
	protected function getEnabledDangerousFunctions(): array {
		$enabled = [];

		foreach (static::DANGEROUS_FUNCTIONS as $func) {
			if (!in_array($func, $this->disabledFunctions, true) && function_exists($func)) {
				$enabled[] = $func;
			}
		}

		return $enabled;
	}

}
