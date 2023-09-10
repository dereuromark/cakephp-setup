<?php

namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Setup\Shell\CurrentConfigShell;
use Shim\TestSuite\ConsoleOutput;

/**
 * CurrentConfig shell test
 */
class CurrentConfigShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\CurrentConfigShell
	 */
	protected $Shell;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMockBuilder(CurrentConfigShell::class)
			->onlyMethods(['in', 'err', '_stop'])
			->setConstructorArgs([$io])
			->getMock();
	}

	/**
	 * @return void
	 */
	public function testConfigure() {
		$this->Shell->runCommand(['configure']);
		$output = $this->out->output();

		$this->assertStringContainsString('[App]', $output);
		$this->assertStringContainsString('[namespace] =>', $output);
	}

	/**
	 * @return void
	 */
	public function testDisplay() {
		$this->Shell->runCommand(['display']);
		$output = $this->out->output();

		$this->assertStringContainsString('Full Base URL:', $output);
	}

	/**
	 * @return void
	 */
	public function testPhpinfo() {
		$this->Shell->runCommand(['phpinfo']);
		$output = $this->out->output();

		$this->assertStringContainsString('session.auto_start', $output);
	}

	/**
	 * @return void
	 */
	public function testValidate() {
		$this->Shell->runCommand(['validate']);
		$output = $this->out->output();

		$this->assertStringContainsString('[driver]', $output);
		$this->assertStringContainsString('[className]', $output);
	}

}
