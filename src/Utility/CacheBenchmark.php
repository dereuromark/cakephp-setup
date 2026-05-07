<?php

declare(strict_types=1);

namespace Setup\Utility;

use Cake\Cache\Engine\ApcuEngine;
use Cake\Cache\Engine\FileEngine;
use Cake\Cache\Engine\MemcachedEngine;
use Cake\Cache\Engine\RedisEngine;
use Cake\Cache\Engine\WincacheEngine;

/**
 * Benchmark CakePHP cache engines and detect their availability.
 */
class CacheBenchmark {

	/**
	 * @var array<string, class-string>
	 */
	protected const ENGINE_CLASSES = [
		'File' => FileEngine::class,
		'Apcu' => ApcuEngine::class,
		'Memcached' => MemcachedEngine::class,
		'Redis' => RedisEngine::class,
		'Wincache' => WincacheEngine::class,
	];

	/**
	 * Returns availability info for each known cache engine.
	 *
	 * @return array<string, array{available: bool, className: string, reason?: string}>
	 */
	public function availableEngines(): array {
		$result = [];
		foreach (static::ENGINE_CLASSES as $name => $class) {
			$result[$name] = $this->probe($name, $class);
		}

		return $result;
	}

	/**
	 * Probe a single engine for availability based on PHP extension presence.
	 *
	 * @param string $name Short engine name (e.g. 'File', 'Redis')
	 * @param class-string $class Fully-qualified engine class name
	 * @return array{available: bool, className: string, reason?: string}
	 */
	protected function probe(string $name, string $class): array {
		$base = ['className' => $class];

		return match ($name) {
			'File' => $base + ['available' => true],
			'Apcu' => extension_loaded('apcu') && (bool)ini_get('apc.enabled')
				? $base + ['available' => true]
				: $base + ['available' => false, 'reason' => 'ext-apcu missing or apc.enabled=0'],
			'Memcached' => extension_loaded('memcached')
				? $base + ['available' => true]
				: $base + ['available' => false, 'reason' => 'ext-memcached missing'],
			'Redis' => extension_loaded('redis')
				? $base + ['available' => true]
				: $base + ['available' => false, 'reason' => 'ext-redis missing'],
			'Wincache' => extension_loaded('wincache') && (bool)ini_get('wincache.ucenabled')
				? $base + ['available' => true]
				: $base + ['available' => false, 'reason' => 'ext-wincache missing or wincache.ucenabled=0'],
			default => $base + ['available' => false, 'reason' => 'unknown engine'],
		};
	}

}
