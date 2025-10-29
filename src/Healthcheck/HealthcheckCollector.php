<?php

namespace Setup\Healthcheck;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\CheckInterface;
use Setup\Healthcheck\Check\Core\CakeSaltCheck;
use Setup\Healthcheck\Check\Core\CakeVersionCheck;
use Setup\Healthcheck\Check\Core\DebugModeDisabledCheck;
use Setup\Healthcheck\Check\Core\FullBaseUrlCheck;
use Setup\Healthcheck\Check\Core\SessionLifetimeCheck;
use Setup\Healthcheck\Check\Database\ConnectCheck;
use Setup\Healthcheck\Check\Environment\PhpExtensionsCheck;
use Setup\Healthcheck\Check\Environment\PhpUploadLimitCheck;
use Setup\Healthcheck\Check\Environment\PhpVersionCheck;

class HealthcheckCollector {

	protected static array $defaultChecks = [
		PhpVersionCheck::class,
		CakeVersionCheck::class,
		CakeSaltCheck::class,
		FullBaseUrlCheck::class,
		SessionLifetimeCheck::class,
		PhpUploadLimitCheck::class,
		PhpExtensionsCheck::class,
		ConnectCheck::class,
		DebugModeDisabledCheck::class,
	];

	/**
	 * @var array<\Setup\Healthcheck\Check\CheckInterface>
	 */
	protected array $checks;

	/**
	 * @return array<class-string<\Setup\Healthcheck\Check\CheckInterface>, mixed>
	 */
	public static function defaultChecks(): array {
		$checks = static::$defaultChecks;

		$result = [];
		foreach ($checks as $check) {
			$result[$check] = [];
		}

		return $result;
	}

	/**
	 * @param array $checks
	 */
	public function __construct(array $checks = []) {
		if (!$checks) {
			$checks = Configure::read('Setup.Healthcheck.checks', static::defaultChecks());
		}

		$this->checks = $this->buildChecks($checks);
	}

	/**
	 * Returns the list of checks to be run.
	 *
	 * @return array<\Setup\Healthcheck\Check\CheckInterface>
	 */
	public function getChecks(): array {
		return $this->checks;
	}

	/**
	 * @return array<string>
	 */
	public function getDomains(): array {
		$domains = [];
		foreach ($this->checks as $check) {
			$domain = $check->domain();
			if (in_array($domain, $domains, true)) {
				continue;
			}

			$domains[] = $domain;
		}

		return $domains;
	}

	/**
	 * @param array $checks
	 * @return array<\Setup\Healthcheck\Check\CheckInterface>
	 */
	protected function buildChecks(array $checks): array {
		$checkInstances = [];
		foreach ($checks as $class => $options) {
			if ($options === false) {
				continue;
			}
			if (is_object($options)) {
				/** @var \Setup\Healthcheck\Check\CheckInterface $options */
				$checkInstances[] = $options;

				continue;
			}
			if (is_numeric($class) && is_string($options)) {
				/** @var class-string<\Setup\Healthcheck\Check\CheckInterface> $options */
				$checkInstances[] = new $options();

				continue;
			}

			/** @var class-string<\Setup\Healthcheck\Check\CheckInterface> $class */
			$checkInstances[] = new $class(...$options);
		}

		foreach ($checkInstances as $key => $checkInstance) {
			if (!$this->isScope($checkInstance)) {
				unset($checkInstances[$key]);
			}
		}

		usort($checkInstances, function (CheckInterface $a, CheckInterface $b) {
			return $a->priority() <=> $b->priority();
		});

		return $checkInstances;
	}

	/**
	 * @param \Setup\Healthcheck\Check\CheckInterface $checkInstance
	 *
	 * @return bool
	 */
	protected function isScope(CheckInterface $checkInstance): bool {
		$scope = $checkInstance->scope();
		if (!$scope) {
			return false;
		}

		foreach ($scope as $condition) {
			if (in_array($condition, [CheckInterface::SCOPE_CLI, CheckInterface::SCOPE_WEB], true)) {
				if (PHP_SAPI === 'cli' && $condition === CheckInterface::SCOPE_CLI) {
					return true;
				}
				if (PHP_SAPI !== 'cli' && $condition === CheckInterface::SCOPE_WEB) {
					return true;
				}
			}
			if (is_callable($condition) && $condition()) {
				return true;
			}
		}

		return false;
	}

}
