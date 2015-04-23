<?php
namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\TestSuite\TestCase;
use Setup\Shell\IndentShell;

/**
 * Class TestCompletionStringOutput
 *
 */
class TestIndentOutput extends ConsoleOutput {

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

		$this->out = new TestIndentOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMock(
			'Setup\Shell\IndentShell',
			['in', 'err', '_stop'],
			[$io]
		);

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
		$output = $this->out->output;
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
		$output = $this->out->output;
		$this->assertContains('found: 1', $output);

		$result = file_get_contents(TMP . 'indent' . DS . 'indent.php');

		$expected = file_get_contents($this->testFilePath . 'indent_again.php');
		$this->assertTextEquals($expected, $result);
	}

}
