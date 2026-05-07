<?php

declare(strict_types=1);

namespace Setup\Test\TestCase\Utility;

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

}
