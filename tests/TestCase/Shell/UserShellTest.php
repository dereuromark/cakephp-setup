<?php

namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Setup\Shell\UserShell;
use Tools\TestSuite\ConsoleOutput;

class UserShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\UserShell|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $Shell;

	/**
	 * @var array
	 */
	public $fixtures = ['plugin.Setup.Users'];

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$this->Shell = $this->getMockBuilder(UserShell::class)
			->setMethods(['in', 'err', '_stop'])
			->setConstructorArgs([$io])
			->getMock();
	}

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->Shell->runCommand(['index']);

		$output = $this->err->output;
		$this->assertEmpty($output, $output);

		$output = $this->out->output();
		$expected = 'mariano';
		$this->assertTextContains($expected, $output, $output);
	}

	/**
	 * @return void
	 */
	public function testUser() {
		$this->Shell->expects($this->at(0))->method('in')
			->will($this->returnValue('y'));
		$this->Shell->expects($this->at(1))->method('in')
			->will($this->returnValue('example@example.de'));
		$this->Shell->expects($this->at(2))->method('in')
			->will($this->returnValue('y'));

		$this->Shell->runCommand(['create', 'example', '123', '-v']);

		$output = $this->err->output;
		$this->assertEmpty($output, $output);

		$output = $this->out->output();
		$expected = '[username] => example';
		$this->assertTextContains($expected, $output, $output);
	}

	/**
	 * @return void
	 */
	public function _testUserInteractive() {
		$this->Shell->expects($this->at(0))->method('in')
			->will($this->returnValue('example'));
		$this->Shell->expects($this->at(1))->method('in')
			->will($this->returnValue('123'));
		$this->Shell->expects($this->at(3))->method('in')
			->will($this->returnValue('example@example.de'));

		$this->Shell->runCommand(['create']);
	}

}
