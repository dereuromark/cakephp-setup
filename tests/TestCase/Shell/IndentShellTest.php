<?php
namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Cake\TestSuite\TestCase;
use Setup\Shell\IndentShell;
use Tools\TestSuite\ConsoleOutput;

class IndentShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\IndentShell|\PHPUnit_Framework_MockObject_MockObject
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

		$this->Shell = $this->getMockBuilder(IndentShell::class)
			->setMethods(['in', 'err', '_stop'])
			->setConstructorArgs([$io])
			->getMock();

		$this->testFilePath = dirname(dirname(dirname(__FILE__))) . DS . 'test_files' . DS;
		if (!is_dir(TMP . 'indent')) {
			mkdir(TMP . 'indent', 0770, true);
		}
		copy($this->testFilePath . 'indent.php', TMP . 'indent' . DS . 'indent.php');
	}

	/**
	 * tearDown
	 *
	 * @return void
	 */
	public function tearDown() {
		if (file_exists(TMP . 'indent' . DS . 'indent.php')) {
			unlink(TMP . 'indent' . DS . 'indent.php');
		}

		parent::tearDown();
		unset($this->Shell);
	}

	/**
	 * Test clean command
	 *
	 * @return void
	 */
	public function testFolder() {
		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('y'));

		$this->Shell->runCommand(['folder', TMP . 'indent' . DS]);
		$output = $this->out->output();
		$this->assertContains('found: 1', $output);

  	$result = file_get_contents(TMP . 'indent' . DS . 'indent.php');

		$expected = file_get_contents($this->testFilePath . 'indent_basic.php');
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * Test clean command
	 *
	 * @return void
	 */
	public function testFolderAgainWithHalf() {
		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('y'));

		$this->Shell->runCommand(['folder', TMP . 'indent' . DS, '-a']);
		$output = $this->out->output();
		$this->assertContains('found: 1', $output);

		$result = file_get_contents(TMP . 'indent' . DS . 'indent.php');

		$expected = file_get_contents($this->testFilePath . 'indent_again.php');
		$this->assertTextEquals($expected, $result);
	}

}
