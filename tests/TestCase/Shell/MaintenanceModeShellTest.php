<?php

namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Setup\Shell\MaintenanceModeShell;
use Tools\TestSuite\ConsoleOutput;
use Tools\TestSuite\TestCase;

class MaintenanceModeShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\MaintenanceModeShell
	 */
	protected $Shell;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$this->Shell = $this->getMockBuilder(MaintenanceModeShell::class)
			->setMethods(['in', 'err', '_stop'])
			->setConstructorArgs([$io])
			->getMock();
		$this->Shell->startup();
		$this->Shell->reset();
	}

	/**
	 * @return void
	 */
	public function testWhitelist() {
		$this->Shell->runCommand(['whitelist']);
		$result = $this->out->output();
		$this->assertTextContains('n/a', $result);

		$this->Shell->runCommand(['whitelist', '192.168.0.1']);
		$result = $this->out->output();
		$this->assertTextContains('192.168.0.1', $result);

		$this->Shell->runCommand(['whitelist', '-r']);
		$result = $this->out->output();
		$this->assertTextContains('n/a', $result);
	}

	/**
	 * @return void
	 */
	public function testStatus() {
		$this->Shell->runCommand(['status']);
		$result = $this->out->output();
		$this->assertTextContains('Maintenance mode not active', $result);

		$this->Shell->runCommand(['activate']);
		$result = $this->out->output();
		$this->assertTextContains('Maintenance mode activated', $result);

		$this->Shell->runCommand(['status']);
		$result = $this->out->output();
		$this->assertTextContains('Maintenance mode active', $result);

		$this->Shell->runCommand(['deactivate']);
		$result = $this->out->output();
		$this->assertTextContains('Maintenance mode deactivated', $result);

		$this->Shell->runCommand(['status']);
		$result = $this->out->output();
		$this->assertTextContains('Maintenance mode not active', $result);
	}

}
