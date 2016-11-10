<?php
namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Cake\TestSuite\TestCase;
use Setup\Shell\TestCliShell;
use Tools\TestSuite\ConsoleOutput;

/**
 */
class TestCliShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\TestCliShell|\PHPUnit_Framework_MockObject_MockObject
	 */
	public $Shell;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMockBuilder(TestCliShell::class)
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
	public function testClean() {
		$this->Shell->runCommand(['router']);
		$output = $this->out->output();

		$this->assertTextContains('Router::url(\'/\')', $output);
		$this->assertTextContains('/test', $output);
	}

}
