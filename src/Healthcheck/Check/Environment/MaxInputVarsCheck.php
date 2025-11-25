<?php

namespace Setup\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Check;

class MaxInputVarsCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if max_input_vars is sufficient for complex forms.';

	/**
	 * Recommended minimum for apps with complex forms.
	 *
	 * @var int
	 */
	protected const RECOMMENDED_MIN = 3000;

	protected int $maxInputVars;

	protected string $level = self::LEVEL_INFO;

	public function __construct() {
		$this->maxInputVars = (int)ini_get('max_input_vars');
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->passed = $this->maxInputVars >= static::RECOMMENDED_MIN;

		$this->infoMessage[] = sprintf('max_input_vars = %d', $this->maxInputVars);

		if (!$this->passed) {
			$this->infoMessage[] = sprintf(
				'Recommended: %d+. Low values can silently truncate POST data in complex forms.',
				static::RECOMMENDED_MIN,
			);
			$this->addFixInstructions();
		}
	}

	/**
	 * Add helpful information about how to increase max_input_vars.
	 *
	 * @return void
	 */
	protected function addFixInstructions(): void {
		$phpIniPath = php_ini_loaded_file();

		if ($phpIniPath) {
			$this->infoMessage[] = 'Loaded Configuration File: `' . $phpIniPath . '`';
			$this->infoMessage[] = 'Quick fix: `sudo sed -i "s/^max_input_vars.*/max_input_vars = ' . static::RECOMMENDED_MIN . '/" ' . escapeshellarg($phpIniPath) . '`';
		}

		$this->infoMessage[] = 'Common values: 3000 (most apps), 5000 (complex admin), 10000 (bulk editing)';
		$this->infoMessage[] = 'Warning: When exceeded, PHP silently drops fields - no error, just missing data.';
	}

}
