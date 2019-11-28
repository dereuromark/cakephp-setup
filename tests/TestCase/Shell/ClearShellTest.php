<?php

namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Setup\Shell\ClearShell;
use Tools\TestSuite\ConsoleOutput;

class ClearShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\ClearShell|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $Shell;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
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
	public function tearDown() {
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

		$this->assertContains('logs' . DS . 'fooo.log', $output);
	}

}
