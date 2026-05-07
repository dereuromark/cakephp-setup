<?php

declare(strict_types=1);

namespace Setup\Test\TestCase\Utility;

use Cake\Cache\Cache;
use PHPUnit\Framework\TestCase;
use Setup\Utility\CacheBenchmark;

/**
 * @uses \Setup\Utility\CacheBenchmark
 */
class CacheBenchmarkTest extends TestCase {

	/**
	 * @return void
	 */
	public function testAvailableEnginesIncludesFile(): void {
		$bench = new CacheBenchmark();
		$availability = $bench->availableEngines();

		$this->assertArrayHasKey('File', $availability);
		$this->assertTrue($availability['File']['available']);
		$this->assertSame('Cake\Cache\Engine\FileEngine', $availability['File']['className']);
	}

	/**
	 * @return void
	 */
	public function testAvailableEnginesReturnsAllExpectedEngineKeys(): void {
		$bench = new CacheBenchmark();
		$availability = $bench->availableEngines();

		$this->assertSame(
			['File', 'Apcu', 'Memcached', 'Redis'],
			array_keys($availability),
		);
	}

	/**
	 * @return void
	 */
	public function testUnavailableEngineHasReason(): void {
		$bench = new CacheBenchmark();
		$availability = $bench->availableEngines();

		foreach ($availability as $entry) {
			if (!$entry['available']) {
				$this->assertArrayHasKey('reason', $entry);
				$this->assertNotSame('', $entry['reason']);
			}
		}
	}

	/**
	 * @return void
	 */
	public function testRunFileEngineProducesReadAndWriteResults(): void {
		$bench = new CacheBenchmark();
		$results = $bench->run(['File']);

		$this->assertArrayHasKey('File', $results);
		$this->assertArrayHasKey('read', $results['File']);
		$this->assertArrayHasKey('write', $results['File']);

		$this->assertArrayHasKey('ms', $results['File']['read']);
		$this->assertArrayHasKey('opsPerSec', $results['File']['read']);
		$this->assertGreaterThan(0.0, $results['File']['read']['ms']);
		$this->assertGreaterThan(0.0, $results['File']['read']['opsPerSec']);

		$this->assertArrayHasKey('ms', $results['File']['write']);
		$this->assertArrayHasKey('opsPerSec', $results['File']['write']);
		$this->assertGreaterThan(0.0, $results['File']['write']['ms']);
		$this->assertGreaterThan(0.0, $results['File']['write']['opsPerSec']);
	}

	/**
	 * @return void
	 */
	public function testRunDoesNotLeaveBehindBenchmarkConfigs(): void {
		$bench = new CacheBenchmark();
		$bench->run(['File']);

		$this->assertNotContains('_setup_benchmark_File', Cache::configured());
	}

	/**
	 * @return void
	 */
	public function testRunWithUnknownEngineReturnsErrorSentinel(): void {
		$bench = new CacheBenchmark();
		$results = $bench->run(['NotAnEngine']);

		$this->assertArrayHasKey('NotAnEngine', $results);
		$this->assertArrayHasKey('error', $results['NotAnEngine']['read']);
		$this->assertArrayHasKey('error', $results['NotAnEngine']['write']);
		$this->assertStringContainsString('Unknown cache engine', $results['NotAnEngine']['read']['error']);
	}

}
