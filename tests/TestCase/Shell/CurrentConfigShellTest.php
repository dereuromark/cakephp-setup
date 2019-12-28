<?php

namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Setup\Shell\CurrentConfigShell;
use Tools\TestSuite\ConsoleOutput;

/**
 * CurrentConfig shell test
 */
class CurrentConfigShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\CurrentConfigShell
	 */
	protected $Shell;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMockBuilder(CurrentConfigShell::class)
			->setMethods(['in', 'err', '_stop'])
			->setConstructorArgs([$io])
			->getMock();
	}

	/**
	 * @return void
	 */
	public function testConfigure() {
		$this->Shell->runCommand(['configure']);
		$output = $this->out->output();

		$this->assertContains('[App]', $output);
		$this->assertContains('[namespace] =>', $output);
	}

	/**
	 * @return void
	 */
	public function testDisplay() {
		$this->Shell->runCommand(['display']);
		$output = $this->out->output();

		$this->assertContains('Full Base URL:', $output);
	}

	/**
	 * @return void
	 */
	public function testPhpinfo() {
		$this->Shell->runCommand(['phpinfo']);
		$output = $this->out->output();

		$this->assertContains('session.auto_start', $output);
	}

	/**
	 * @return void
	 */
	public function testValidate() {
		$this->Shell->runCommand(['validate']);
		$output = $this->out->output();

		$this->assertContains('[driver]', $output);
		$this->assertContains('[className]', $output);
	}

}
