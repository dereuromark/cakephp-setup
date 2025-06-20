<?php

namespace Setup\Healthcheck;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Core\CakeSaltCheck;
use Setup\Healthcheck\Check\Core\CakeVersionCheck;
use Setup\Healthcheck\Check\Core\FullBaseUrlCheck;
use Setup\Healthcheck\Check\Database\ConnectCheck;
use Setup\Healthcheck\Check\Environment\PhpUploadLimitCheck;
use Setup\Healthcheck\Check\Environment\PhpVersionCheck;

class HealthcheckCollector {

	protected static array $defaultChecks = [
		PhpVersionCheck::class,
		CakeVersionCheck::class,
		CakeSaltCheck::class,
		FullBaseUrlCheck::class,
		PhpUploadLimitCheck::class,
		ConnectCheck::class,
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
	protected function buildChecks(mixed $checks): array {
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

		return $checkInstances;
	}

}
