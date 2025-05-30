<?php
declare(strict_types=1);

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Setup\Healthcheck\Check\Environment\PhpVersionCheck;

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

}
