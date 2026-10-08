<?php

namespace Setup\Healthcheck;

use Cake\Cache\Cache;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use Setup\Healthcheck\Check\CachedCheck;
use Setup\Healthcheck\Check\Check;
use Setup\Healthcheck\Check\CheckInterface;
use WeakMap;

class Healthcheck {

	/**
     * @var array
     */
	protected const RESULT_PROPERTIES = ['passed', 'successMessage', 'warningMessage', 'failureMessage', 'infoMessage'];

	/**
     * @var int
     */
	protected const FINGERPRINT_DEPTH = 5;

	/**
	 * @var \WeakMap<\Setup\Healthcheck\Check\CheckInterface, array{revision: int, fingerprint: string}>|null
	 */
	protected static ?WeakMap $fingerprints = null;

	/**
	 * @var \Setup\Healthcheck\HealthcheckCollector
	 */
	protected HealthcheckCollector $collector;

	protected bool $passed = true;

	protected CollectionInterface $result;

	/**
	 * @param \Setup\Healthcheck\HealthcheckCollector $collector
	 */
	public function __construct(HealthcheckCollector $collector) {
		$this->collector = $collector;
		$this->result = new Collection([]);
	}

	/**
	 * @param string|null $domain
	 * @param bool $useCache Skip cache reads when false; fresh results still update the cache.
	 * @return bool
	 */
	public function run(?string $domain = null, bool $useCache = true): bool {
		$checks = $this->collector->getChecks();
		$config = Configure::read('Setup.Healthcheck.cache');
		$keys = $config ? $this->cacheKeys() : [];
		foreach ($checks as $check) {
			if ($domain && $check->domain() !== $domain) {
				continue;
			}

			$ttl = $check instanceof Check ? ($check->cacheTtl() ?? (int)Configure::read('Setup.Healthcheck.cacheTtl', 60)) : 0;
			$pool = $config && $ttl > 0 ? Cache::pool($config) : null;
			$key = $keys[spl_object_id($check)] ?? null;
			$data = $pool && $key && $useCache ? $pool->get($key) : null;
			if (is_array($data) && (int)($data['cachedAt'] ?? 0) + $ttl >= time()) {
				$check = CachedCheck::fromArray($data);
			} else {
				$check->check();

				// If check passed but has warnings, treat it as a warning-level failure
				if ($check->passed() && $check->warningMessage()) {
					$reflection = new ReflectionClass($check);
					$passedProperty = $reflection->getProperty('passed');
					$passedProperty->setValue($check, false);

					$levelProperty = $reflection->getProperty('level');
					$levelProperty->setValue($check, CheckInterface::LEVEL_WARNING);
				}

				if ($pool && $key) {
					$pool->set($key, CachedCheck::fromCheck($check)->toArray(), $ttl);
				}
			}

			if (!$check->passed() && $check->level() === CheckInterface::LEVEL_ERROR) {
				$this->passed = false;
			}

			$this->result = $this->result->appendItem($check);
		}

		return $this->passed;
	}

	/**
	 * @return void
	 */
	public function clearCache(): void {
		$config = Configure::read('Setup.Healthcheck.cache');
		if (!$config) {
			return;
		}

		Cache::pool($config)->deleteMultiple(array_values($this->cacheKeys()));
	}

	/**
	 * Keys are derived from each check's configuration; fingerprint() keeps them stable across execution.
	 *
	 * @return array<int, string>
	 */
	protected function cacheKeys(): array {
		$sapi = PHP_SAPI === 'cli' ? 'cli' : 'web';
		$keys = [];
		foreach ($this->collector->getChecks() as $check) {
			$keys[spl_object_id($check)] = 'setup_healthcheck_' . $sapi . '_' . md5($check::class . '|' . $this->fingerprint($check));
		}

		return $keys;
	}

	/**
	 * Memoized per object: execution mutates state (e.g. level, message caches), reused instances must keep their key.
	 *
	 * @param \Setup\Healthcheck\Check\CheckInterface $check
	 * @return string
	 */
	protected function fingerprint(CheckInterface $check): string {
		static::$fingerprints ??= new WeakMap();
		$revision = $check instanceof Check ? $check->revision() : 0;
		if (isset(static::$fingerprints[$check]) && static::$fingerprints[$check]['revision'] === $revision) {
			return static::$fingerprints[$check]['fingerprint'];
		}

		// Array cast also exposes inherited private properties (as "\0Class\0name").
		$state = [];
		foreach ((array)$check as $name => $value) {
			$parts = explode("\0", (string)$name);
			if (in_array(end($parts), static::RESULT_PROPERTIES, true)) {
				continue;
			}

			$state[$name] = $this->normalize($value);
		}

		$fingerprint = serialize($state);
		static::$fingerprints[$check] = ['revision' => $revision, 'fingerprint' => $fingerprint];

		return $fingerprint;
	}

	/**
	 * @param mixed $value
	 * @param int $depth
	 * @return mixed
	 */
	protected function normalize(mixed $value, int $depth = 0): mixed {
		if ($depth > static::FINGERPRINT_DEPTH) {
			return '...';
		}
		if ($value instanceof Closure) {
			$function = new ReflectionFunction($value);

			return [
				Closure::class => $function->getFileName() . ':' . $function->getStartLine(),
				'use' => $this->normalize($function->getStaticVariables(), $depth + 1),
				'this' => $this->normalize($function->getClosureThis(), $depth + 1),
			];
		}
		if (is_object($value)) {
			return [$value::class => $this->normalize((array)$value, $depth + 1)];
		}
		if (is_array($value)) {
			return array_map(fn ($item) => $this->normalize($item, $depth + 1), $value);
		}

		return $value;
	}

	/**
	 * @return int
	 */
	public function errors(): int {
		return $this->result->filter(function (CheckInterface $check) {
			return !$check->passed() && $check->level() === CheckInterface::LEVEL_ERROR;
		})->count();
	}

	/**
	 * @return int
	 */
	public function warnings(): int {
		return $this->result->filter(function (CheckInterface $check) {
			return !$check->passed() && $check->level() === CheckInterface::LEVEL_WARNING;
		})->count();
	}

	/**
	 * @return \Cake\Collection\CollectionInterface<\Setup\Healthcheck\Check\CheckInterface>
	 */
	public function result(): CollectionInterface {
		return $this->result->groupBy(function (CheckInterface $result) {
			return $result->domain();
		});
	}

	/**
	 * @return array<string>
	 */
	public function domains(): array {
		return $this->collector->getDomains();
	}

	/**
	 * @return \Setup\Healthcheck\HealthcheckCollector
	 */
	public function collector(): HealthcheckCollector {
		return $this->collector;
	}

}
