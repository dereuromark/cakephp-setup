<?php
declare(strict_types=1);

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

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
		$this->exec('healthcheck');

		$this->assertOutputContains('=> OK');
	}

}
