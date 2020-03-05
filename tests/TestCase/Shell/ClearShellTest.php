<?php

namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Setup\Shell\ClearShell;
use Shim\TestSuite\ConsoleOutput;

class ClearShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\ClearShell|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $Shell;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMockBuilder(ClearShell::class)
			->setMethods(['in', 'err', '_stop'])
			->setConstructorArgs([$io])
			->getMock();
	}

	/**
	 * tearDown
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		unset($this->Shell);
	}

	/**
	 * Test clean command
	 *
	 * @return void
	 */
	public function testClearLogs() {
		if (!is_dir(LOGS)) {
			mkdir(LOGS, 0775, true);
		}
		$file = LOGS . 'fooo.log';
		file_put_contents($file, 'Bla');
		$this->assertTrue(file_exists($file));

		$this->Shell->runCommand(['logs', '-v']);
		$output = $this->out->output();

		$this->assertStringContainsString('logs' . DS . 'fooo.log', $output);
	}

}
