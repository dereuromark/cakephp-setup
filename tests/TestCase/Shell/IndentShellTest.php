<?php
namespace Setup\Test\TestCase\Shell;

use Setup\Shell\IndentShell;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Class TestCompletionStringOutput
 *
 */
class TestWhitespaceOutput extends ConsoleOutput {

	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}

/**
 */
class IndentShellTest extends TestCase {

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->out = new TestWhitespaceOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMock(
			'Setup\Shell\IndentShell',
			['in', 'err', '_stop'],
			[$io]
		);

		$this->testFilePath = dirname(dirname(dirname(__FILE__))) . DS . 'test_files' . DS;
		copy($this->testFilePath . 'indent.php', TMP . 'indent.php');
	}

	/**
	 * tearDown
	 *
	 * @return void
	 */
	public function tearDown() {
		if (file_exists(TMP . 'indent.php')) {
			//unlink(TMP . 'indent.php');
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


		$this->Shell->runCommand(['folder', TMP]);
		$output = $this->out->output;
		$this->assertContains('found: 1', $output);

  	$result = file_get_contents(TMP . 'indent.php');
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


		$this->Shell->runCommand(['folder', TMP, '-a']);
		$output = $this->out->output;
		$this->assertContains('found: 1', $output);

		$result = file_get_contents(TMP . 'indent.php');
		$expected = file_get_contents($this->testFilePath . 'indent_again.php');
		$this->assertTextEquals($expected, $result);
	}

}
