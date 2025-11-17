<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * UserUpdate command test
 *
 * @uses \Setup\Command\BakeHealthcheckCommand
 */
class BakeHealthcheckCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->loadPlugins(['Setup']);
	}

	/**
	 * @return void
	 */
	public function testUpdate() {
		$this->skipIf(true, '//FIXME: No bake template found for "Setup.Healthcheck/check" skipping file generation.');

		$this->exec('bake healthcheck Group Test');

		$output = $this->_out->output();
		$this->assertStringContainsString('Creating file', $output);
		$this->assertStringContainsString('<success>Wrote</success>', $output);
	}

}
