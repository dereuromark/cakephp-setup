<?php
declare(strict_types=1);

namespace Setup\Test\TestCase\Command;

use Cake\Cache\Cache;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Setup\Healthcheck\Check\Environment\PhpVersionCheck;
use TestApp\Healthcheck\Check\CountingCheck;

/**
 * Setup\Command\HealthcheckCommand Test Case
 *
 * @uses \Setup\Command\HealthcheckCommand
 */
class HealthcheckCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->loadPlugins(['Setup']);
	}

	/**
	 * Test defaultName method
	 *
	 * @return void
	 */
	public function testExecute(): void {
		Configure::write('Setup.Healthcheck.checks', [
			PhpVersionCheck::class,
		]);

		Configure::write('App.fullBaseUrl', 'https://example.com');

		$this->exec('healthcheck -v');

		$this->assertExitSuccess();
		$this->assertOutputContains('=> OK');
	}

	public function testNoCache(): void {
		Cache::setConfig('healthcheck_test', ['className' => 'Array']);
		Configure::write('Setup.Healthcheck', ['cache' => 'healthcheck_test', 'checks' => [new CountingCheck(outcome: true)]]);
		CountingCheck::$runs = 0;
		try {
			$this->exec('healthcheck');
			$this->exec('healthcheck');
			$this->assertSame(1, CountingCheck::$runs);
			$this->exec('healthcheck --no-cache');
			$this->assertExitSuccess();
			$this->assertSame(2, CountingCheck::$runs);
		} finally {
			Cache::drop('healthcheck_test');
			Configure::delete('Setup.Healthcheck');
		}
	}

}
