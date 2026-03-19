<?php
declare(strict_types=1);

namespace Setup\Healthcheck\Check\Environment;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class DiskSpaceCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if disk space is sufficient.';

	protected string $level = self::LEVEL_WARNING;

	protected int $warningThresholdPercent;

	protected int $errorThresholdPercent;

	/**
	 * @var array<string>
	 */
	protected array $paths;

	/**
	 * @param int $warningThresholdPercent Warn when disk usage exceeds this percentage (default: 80)
	 * @param int $errorThresholdPercent Error when disk usage exceeds this percentage (default: 95)
	 * @param array<string>|null $paths Paths to check. Defaults to ROOT if not specified.
	 */
	public function __construct(
		int $warningThresholdPercent = 80,
		int $errorThresholdPercent = 95,
		?array $paths = null,
	) {
		$this->warningThresholdPercent = $warningThresholdPercent;
		$this->errorThresholdPercent = $errorThresholdPercent;
		$this->paths = $paths ?? (array)Configure::read('Healthcheck.diskSpacePaths', [ROOT]);
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$this->passed = true;
		$hasWarning = false;
		$hasError = false;

		foreach ($this->paths as $path) {
			$result = $this->checkPath($path);
			if ($result === null) {
				continue;
			}

			if ($result['error']) {
				$hasError = true;
			} elseif ($result['warning']) {
				$hasWarning = true;
			}
		}

		if ($hasError) {
			$this->passed = false;
			$this->level = static::LEVEL_ERROR;
		} elseif ($hasWarning) {
			$this->passed = false;
			$this->level = static::LEVEL_WARNING;
		}
	}

	/**
	 * Check disk space for a specific path.
	 *
	 * @param string $path The path to check
	 * @return array{error: bool, warning: bool}|null
	 */
	protected function checkPath(string $path): ?array {
		$free = @disk_free_space($path);
		$total = @disk_total_space($path);

		if ($free === false || $total === false) {
			$this->failureMessage[] = "Unable to determine disk space for `{$path}`.";

			return ['error' => true, 'warning' => false];
		}

		$usedPercent = (int)(($total - $free) / $total * 100);
		$freeGB = round($free / 1024 / 1024 / 1024, 2);
		$totalGB = round($total / 1024 / 1024 / 1024, 2);

		if ($usedPercent >= $this->errorThresholdPercent) {
			$this->failureMessage[] = "Disk usage critical for `{$path}`: {$usedPercent}% used ({$freeGB} GB free of {$totalGB} GB).";

			return ['error' => true, 'warning' => false];
		}

		if ($usedPercent >= $this->warningThresholdPercent) {
			$this->warningMessage[] = "Disk usage high for `{$path}`: {$usedPercent}% used ({$freeGB} GB free of {$totalGB} GB).";

			return ['error' => false, 'warning' => true];
		}

		$this->infoMessage[] = "Disk usage for `{$path}`: {$usedPercent}% used ({$freeGB} GB free of {$totalGB} GB).";

		return ['error' => false, 'warning' => false];
	}

}
