<?php

namespace Setup\Healthcheck\Check\Core;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class CakeCacheCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the CakePHP cache is set up.';

	protected array $defaultCacheKeys = [
		'default',
		'_cake_model_',
		'_cake_translations_',
	];

	protected array $missing = [];

	/**
	 * @return void
	 */
	public function check(): void {
		$this->passed = $this->assertCache();
	}

	/**
	 * @return string[]
	 */
	public function failureMessage(): array {
		return [
			'The following cache setups are missing: ' . implode(', ', $this->missing) . '.',
		];
	}

	/**
	 * @return bool
	 */
	protected function assertCache(): bool {
		$cacheKeys = $this->defaultCacheKeys;
		$additional = (array)Configure::read('Healthcheck.checkCacheKeys');
		foreach ($additional as $key) {
			if (!in_array($key, $cacheKeys, true)) {
				$cacheKeys[] = $key;
			}
		}

		$issues = [];
		foreach ($cacheKeys as $cacheKey) {
			if (Cache::getConfig($cacheKey)) {
				continue;
			}

			$issues[] = $cacheKey;
		}

		$this->missing = $issues;

		return !$this->missing;
	}

}
