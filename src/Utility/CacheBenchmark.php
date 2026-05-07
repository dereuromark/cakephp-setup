<?php

declare(strict_types=1);

namespace Setup\Utility;

use Cake\Cache\Cache;
use Cake\Cache\Engine\ApcuEngine;
use Cake\Cache\Engine\FileEngine;
use Cake\Cache\Engine\MemcachedEngine;
use Cake\Cache\Engine\RedisEngine;
use InvalidArgumentException;
use Throwable;

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
	];

	/**
	 * @var int
	 */
	protected const READ_OPS = 1000;

	/**
	 * @var int
	 */
	protected const WRITE_OPS = 1000;

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
	 * @param array<string> $engineNames Subset of keys from availableEngines() where available === true
	 * @return array<string, array<string, array{ms: float, opsPerSec: float}|array{error: string}>>
	 */
	public function run(array $engineNames): array {
		$payload = str_repeat('x', 100); // ~100B
		$results = [];

		foreach ($engineNames as $engine) {
			$results[$engine] = $this->runEngine($engine, $payload);
		}

		return $results;
	}

	/**
	 * @param string $engine
	 * @param string $payload
	 * @return array<string, array{ms: float, opsPerSec: float}|array{error: string}>
	 */
	protected function runEngine(string $engine, string $payload): array {
		$configName = '_setup_benchmark_' . $engine;
		$result = [];

		$readDone = false;
		try {
			Cache::setConfig($configName, $this->buildConfig($engine));

			// Pre-populate read keyspace (silent, not measured)
			for ($i = 0; $i < static::READ_OPS; $i++) {
				Cache::write('bench_read_' . $i, $payload, $configName);
			}

			// Read benchmark
			$start = hrtime(true);
			for ($i = 0; $i < static::READ_OPS; $i++) {
				Cache::read('bench_read_' . $i, $configName);
			}
			$result['read'] = $this->measure($start, static::READ_OPS);
			$readDone = true;

			// Write benchmark (separate keyspace from read keys)
			$start = hrtime(true);
			for ($i = 0; $i < static::WRITE_OPS; $i++) {
				Cache::write('bench_write_' . $i, $payload, $configName);
			}
			$result['write'] = $this->measure($start, static::WRITE_OPS);
		} catch (Throwable $e) {
			$message = $e->getMessage() !== '' ? $e->getMessage() : $e::class;
			if (!$readDone) {
				$result['read'] = ['error' => $message];
			}
			$result['write'] = ['error' => $message];
		} finally {
			try {
				Cache::clear($configName);
			} catch (Throwable) {
				// best-effort cleanup
			}
			Cache::drop($configName);
		}

		return $result;
	}

	/**
	 * @param int $start hrtime(true) start
	 * @param int $ops
	 * @return array{ms: float, opsPerSec: float}
	 */
	protected function measure(int $start, int $ops): array {
		$ns = hrtime(true) - $start;
		$ms = $ns / 1_000_000;
		$opsPerSec = $ms > 0 ? $ops / ($ms / 1000) : 0.0;

		return ['ms' => round($ms, 2), 'opsPerSec' => round($opsPerSec, 0)];
	}

	/**
	 * @param string $engine
	 * @throws \InvalidArgumentException If $engine is not a known engine name
	 * @return array<string, mixed>
	 */
	protected function buildConfig(string $engine): array {
		if (!isset(static::ENGINE_CLASSES[$engine])) {
			throw new InvalidArgumentException(sprintf('Unknown cache engine: %s', $engine));
		}

		$base = [
			'className' => static::ENGINE_CLASSES[$engine],
			'prefix' => 'setup_benchmark_',
			'duration' => '+5 minutes',
		];

		return match ($engine) {
			'File' => $base + ['path' => TMP . 'cache' . DS . 'setup_benchmark' . DS],
			default => $base,
		};
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
			'Apcu' => extension_loaded('apcu')
				? $base + ['available' => true]
				: $base + ['available' => false, 'reason' => 'ext-apcu missing'],
			'Memcached' => extension_loaded('memcached')
				? $base + ['available' => true]
				: $base + ['available' => false, 'reason' => 'ext-memcached missing'],
			'Redis' => extension_loaded('redis')
				? $base + ['available' => true]
				: $base + ['available' => false, 'reason' => 'ext-redis missing'],
			default => $base + ['available' => false, 'reason' => 'unknown engine'],
		};
	}

}
