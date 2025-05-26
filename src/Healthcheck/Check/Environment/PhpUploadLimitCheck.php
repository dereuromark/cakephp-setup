<?php

namespace Setup\Healthcheck\Check\Environment;

use Setup\Healthcheck\Check\Check;

class PhpUploadLimitCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the PHP upload limit is at least the required minimum.';

	/**
	 * @var int MB
	 */
	protected const DEFAULT_MIN = 16;

	protected int $min;

	/**
	 * @param int|null $min
	 */
	public function __construct(?int $min = null) {
		if ($min === null) {
			$min = static::DEFAULT_MIN;
		}

		$this->min = $min;
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->assertMinimum();
	}

	/**
	 * @return void
	 */
	protected function assertMinimum(): void {
		$uploadMax = $this->toBytes((string)ini_get('upload_max_filesize'));
		$postMax = $this->toBytes((string)ini_get('post_max_size'));

		$this->passed = true;
		$min = $this->min * 1024 * 1024; // Convert MB to bytes
		if ($min > $uploadMax) {
			$this->failureMessage[] = 'The PHP upload limit is too low. It is currently set to `' . ini_get('upload_max_filesize') . '`, but at least ' . $this->min . ' MB is required.';

			$this->passed = false;
		}
		if ($min > $postMax) {
			$this->failureMessage[] = 'The PHP post limit is too low. It is currently set to `' . ini_get('post_max_size') . '`, but at least ' . $this->min . ' MB is required.';

			$this->passed = false;
		}

		if (!$this->passed) {
			return;
		}

		$this->infoMessage[] = 'The PHP upload limit is set to `' . ini_get('upload_max_filesize') . '` and the post limit is set to `' . ini_get('post_max_size') . '`.';
	}

	/**
	 * @param string $val
	 * @return int
	 */
	protected function toBytes(string $val): int {
		$val = trim($val);
		$unit = strtolower($val[strlen($val) - 1]);
		$bytes = (int)$val;

		switch ($unit) {
			case 'g':
				$bytes *= 1024;
				// Continue
			case 'm':
				$bytes *= 1024;
				// Continue
			case 'k':
				$bytes *= 1024;
				// Continue
		}

		return $bytes;
	}

}
