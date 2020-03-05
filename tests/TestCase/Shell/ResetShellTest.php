<?php

namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Setup\Shell\ResetShell;
use Shim\TestSuite\ConsoleOutput;

class ResetShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\ResetShell|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $Shell;

	/**
	 * @var array
	 */
	public $fixtures = ['plugin.Setup.Users'];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$this->Shell = $this->getMockBuilder(ResetShell::class)
			->setMethods(['in', '_stop'])
			->setConstructorArgs([$io])
			->getMock();
	}

	/**
	 * @return void
	 */
	public function testEmail() {
		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('example@example.de'));

		$this->Shell->runCommand(['email']);
		$output = $this->out->output();

		$expected = '1 emails resetted';
		$this->assertTextContains($expected, (string)$output);
	}

	/**
	 * @return void
	 */
	public function testEmailQuick() {
		$this->Shell->runCommand(['email', 'example@example.de']);
		$output = $this->out->output();

		$expected = '1 emails resetted';
		$this->assertTextContains($expected, (string)$output);
	}

	/**
	 * @return void
	 */
	public function testPwd() {
		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('123'));

		$this->Shell->runCommand(['pwd']);
		$output = $this->out->output();

		$expected = '1 pwds resetted';
		$this->assertTextContains($expected, (string)$output);
	}

	/**
	 * @return void
	 */
	public function testPwdQuick() {
		$this->Shell->runCommand(['pwd', '123']);
		$output = $this->out->output();

		$expected = '1 pwds resetted';
		$this->assertTextContains($expected, (string)$output);
	}

}
