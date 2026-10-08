<?php

namespace Setup\Test\TestCase\Healthcheck;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Closure;
use InvalidArgumentException;
use ReflectionMethod;
use Setup\Healthcheck\Check\CachedCheck;
use Setup\Healthcheck\Check\CheckInterface;
use Setup\Healthcheck\Check\Core\SecurityHeadersCheck;
use Setup\Healthcheck\Healthcheck;
use Setup\Healthcheck\HealthcheckCollector;
use TestApp\Healthcheck\Check\CountingCheck;
use TestApp\Healthcheck\Check\PrivateOptionCheck;

class HealthcheckTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		Cache::setConfig('healthcheck_test', ['className' => 'Array']);
		Configure::write('Setup.Healthcheck.cache', 'healthcheck_test');
		CountingCheck::$runs = 0;
	}

	public function tearDown(): void {
		Cache::drop('healthcheck_test');
		Configure::delete('Setup.Healthcheck');
		parent::tearDown();
	}

	protected function runChecks(array $checks, bool $useCache = true, ?string $domain = null): Healthcheck {
		$healthcheck = new Healthcheck(new HealthcheckCollector($checks));
		$healthcheck->run($domain, $useCache);

		return $healthcheck;
	}

	public function testAdjustLevel(): void {
		$check = new CountingCheck();
		foreach ([CheckInterface::LEVEL_ERROR, CheckInterface::LEVEL_WARNING, CheckInterface::LEVEL_INFO] as $level) {
			$this->assertSame($check, $check->adjustLevel($level));
			$this->assertSame($level, $check->level());
		}
		$check->adjustLevel(CheckInterface::LEVEL_WARNING);
		$healthcheck = new Healthcheck(new HealthcheckCollector([$check]));
		$this->assertTrue($healthcheck->run());
		$this->assertSame(0, $healthcheck->errors());
		$this->assertSame(1, $healthcheck->warnings());
	}

	public function testInvalidLevel(): void {
		$this->expectException(InvalidArgumentException::class);
		(new CountingCheck())->adjustLevel('invalid');
	}

	public function testInvalidTtl(): void {
		$this->expectException(InvalidArgumentException::class);
		(new CountingCheck())->adjustCacheTtl(-1);
	}

	public function testCacheAndBypass(): void {
		$fresh = $this->runChecks([new CountingCheck()]);
		$cached = $this->runChecks([new CountingCheck()]);
		$this->assertSame(1, CountingCheck::$runs);
		$entry = $cached->result()->unfold()->first();
		$this->assertInstanceOf(CachedCheck::class, $entry);
		$this->assertFalse($entry->passed());
		$this->assertSame(CheckInterface::LEVEL_ERROR, $entry->level());
		$this->assertSame(['Failed'], $entry->failureMessage());
		$this->assertSame(['Run 1'], $entry->infoMessage());
		$this->assertSame($fresh->errors(), $cached->errors());
		$this->assertSame([], $entry->scope());
		$this->runChecks([new CountingCheck()], false);
		$this->assertSame(2, CountingCheck::$runs);
		$entry = $this->runChecks([new CountingCheck()])->result()->unfold()->first();
		$this->assertSame(['Run 2'], $entry->infoMessage());
	}

	public function testWarningPostProcessingCached(): void {
		$this->runChecks([new CountingCheck(outcome: true, warn: true)]);
		$cached = $this->runChecks([new CountingCheck(outcome: true, warn: true)]);
		$this->assertSame(1, $cached->warnings());
		$this->assertSame(0, $cached->errors());
		$this->assertSame(['Warning'], $cached->result()->unfold()->first()->warningMessage());
	}

	public function testDisabledCache(): void {
		$check = (new CountingCheck())->adjustCacheTtl(0);
		$this->assertSame(0, $check->cacheTtl());
		$this->runChecks([$check]);
		$this->runChecks([$check]);
		$this->assertSame(2, CountingCheck::$runs);
		Configure::delete('Setup.Healthcheck.cache');
		$this->runChecks([new CountingCheck()]);
		$this->runChecks([new CountingCheck()]);
		$this->assertSame(4, CountingCheck::$runs);
	}

	public function testDistinctInstancesAndDomainFiltering(): void {
		$checks = [new CountingCheck('First'), new CountingCheck('Second', true)];
		$this->runChecks($checks);
		$cached = $this->runChecks($checks, domain: 'Second');
		$this->assertSame(2, CountingCheck::$runs);
		$this->assertTrue($cached->result()->unfold()->first()->passed());
		$this->assertSame(['Run 2'], $cached->result()->unfold()->first()->infoMessage());

		$keys = (new ReflectionMethod(Healthcheck::class, 'cacheKeys'))->invoke(new Healthcheck(new HealthcheckCollector($checks)));
		$this->assertCount(2, array_unique($keys));
		foreach ($keys as $key) {
			$this->assertStringStartsWith('setup_healthcheck_cli_', $key);
		}
	}

	public function testReusedInstanceKeepsCacheKey(): void {
		$check = new CountingCheck(outcome: true, warn: true);
		$this->runChecks([$check]);
		$this->assertSame(CheckInterface::LEVEL_WARNING, $check->level());
		$healthcheck = $this->runChecks([$check]);
		$this->assertSame(1, CountingCheck::$runs);
		$this->assertInstanceOf(CachedCheck::class, $healthcheck->result()->unfold()->first());

		(new Healthcheck(new HealthcheckCollector([$check])))->clearCache();
		$this->runChecks([$check]);
		$this->assertSame(2, CountingCheck::$runs);
	}

	public function testAdjustingAfterRunOnSameHealthcheck(): void {
		$check = new CountingCheck();
		$healthcheck = new Healthcheck(new HealthcheckCollector([$check]));
		$healthcheck->run();
		$check->adjustLevel(CheckInterface::LEVEL_WARNING);
		$healthcheck->run();
		$this->assertSame(2, CountingCheck::$runs);
	}

	public function testLoweredDefaultTtlExpiresOlderEntries(): void {
		Configure::write('Setup.Healthcheck.cacheTtl', 3600);
		$check = new CountingCheck();
		$healthcheck = new Healthcheck(new HealthcheckCollector([$check]));
		$key = (new ReflectionMethod(Healthcheck::class, 'cacheKeys'))->invoke($healthcheck)[spl_object_id($check)];
		$healthcheck->run();
		$data = Cache::pool('healthcheck_test')->get($key);
		$data['cachedAt'] = time() - 120;
		Cache::pool('healthcheck_test')->set($key, $data);

		Configure::write('Setup.Healthcheck.cacheTtl', 60);
		$this->runChecks([$check]);
		$this->assertSame(2, CountingCheck::$runs);
	}

	public function testClosureOptionsAreFingerprinted(): void {
		$keys = [];
		foreach (['a', 'b'] as $service) {
			$check = new CountingCheck();
			$check->options = (object)['callback' => fn () => $service];
			$keys[] = (new ReflectionMethod(Healthcheck::class, 'cacheKeys'))->invoke(new Healthcheck(new HealthcheckCollector([$check])));
		}
		$this->assertNotSame($keys[0], $keys[1]);
	}

	public function testInheritedPrivateOptionsAreFingerprinted(): void {
		$this->runChecks([new PrivateOptionCheck(true)]);
		$healthcheck = $this->runChecks([new PrivateOptionCheck(false)]);
		$this->assertFalse($healthcheck->result()->unfold()->first()->passed());
	}

	public function testClosureBindingIsFingerprinted(): void {
		$keys = [];
		foreach ([false, true] as $outcome) {
			$check = new CountingCheck(outcome: $outcome);
			$check->options = (object)[
				'callback' => Closure::bind(function () {
					return $this->outcome;
				}, $check, CountingCheck::class),
			];
			$keys[] = (new ReflectionMethod(Healthcheck::class, 'cacheKeys'))->invoke(new Healthcheck(new HealthcheckCollector([$check])));
		}
		$this->assertNotSame($keys[0], $keys[1]);
	}

	public function testSecurityHeadersCheckIsNeverCached(): void {
		$this->assertSame(0, (new SecurityHeadersCheck())->cacheTtl());
	}

	public function testAdjustingReusedInstanceChangesCacheKey(): void {
		$check = new CountingCheck();
		$this->assertFalse($this->runChecks([$check])->run());
		$check->adjustLevel(CheckInterface::LEVEL_WARNING);
		$healthcheck = $this->runChecks([$check]);
		$this->assertSame(2, CountingCheck::$runs);
		$this->assertSame(0, $healthcheck->errors());
		$this->assertSame(1, $healthcheck->warnings());
	}

	public function testObjectOptionsAreFingerprinted(): void {
		$keys = [];
		foreach ([false, true] as $outcome) {
			$check = new CountingCheck();
			$check->options = (object)['outcome' => $outcome];
			$keys[] = (new ReflectionMethod(Healthcheck::class, 'cacheKeys'))->invoke(new Healthcheck(new HealthcheckCollector([$check])));
		}
		$this->assertNotSame($keys[0], $keys[1]);
	}

	public function testDifferentlyConfiguredInstancesDoNotShareCache(): void {
		$this->runChecks([new CountingCheck()]);
		$healthcheck = $this->runChecks([new CountingCheck(outcome: true)]);
		$this->assertSame(2, CountingCheck::$runs);
		$this->assertTrue($healthcheck->result()->unfold()->first()->passed());

		$downgraded = $this->runChecks([(new CountingCheck())->adjustLevel(CheckInterface::LEVEL_WARNING)]);
		$this->assertSame(3, CountingCheck::$runs);
		$this->assertSame(0, $downgraded->errors());
		$this->assertSame(1, $downgraded->warnings());

		$this->runChecks([new CountingCheck()]);
		$this->assertSame(3, CountingCheck::$runs);
	}

	public function testTtlInheritanceAndOverride(): void {
		Configure::write('Setup.Healthcheck.cacheTtl', 0);
		$check = new CountingCheck();
		$this->assertNull($check->cacheTtl());
		$this->runChecks([$check]);
		$this->runChecks([$check]);
		$this->assertSame(2, CountingCheck::$runs);
		$this->assertSame($check, $check->adjustCacheTtl(30));
		$this->runChecks([$check]);
		$this->runChecks([$check]);
		$this->assertSame(3, CountingCheck::$runs);
		$check->adjustCacheTtl(null);
		$this->runChecks([$check]);
		$this->assertSame(4, CountingCheck::$runs);
	}

	public function testInterfaceOnlyCheckNeverCached(): void {
		$check = $this->createMock(CheckInterface::class);
		$check->method('scope')->willReturn([CheckInterface::SCOPE_CLI]);
		$check->method('priority')->willReturn(5);
		$check->method('passed')->willReturn(false);
		$check->method('level')->willReturn(CheckInterface::LEVEL_ERROR);
		$check->expects($this->exactly(2))->method('check');
		$this->runChecks([$check]);
		$this->runChecks([$check]);
	}

	public function testClearCache(): void {
		$checks = [new CountingCheck(), new CountingCheck('Other')];
		$this->runChecks($checks)->clearCache();
		$this->runChecks($checks);
		$this->assertSame(4, CountingCheck::$runs);
		Configure::delete('Setup.Healthcheck.cache');
		$this->runChecks($checks)->clearCache();
	}

	public function testSnapshotRoundTrip(): void {
		$check = new CountingCheck(outcome: true, warn: true);
		$check->adjustScope([static fn () => true]);
		$check->check();
		$snapshot = CachedCheck::fromCheck($check, 123);
		$restored = CachedCheck::fromArray($snapshot->toArray());
		$restored->check();
		$this->assertSame(1, CountingCheck::$runs);
		$this->assertSame(123, $restored->cachedAt());
		$this->assertSame(['Passed'], $restored->successMessage());
		$this->assertSame($snapshot->toArray(), $restored->toArray());
		$this->assertArrayNotHasKey('scope', $restored->toArray());
	}

}
