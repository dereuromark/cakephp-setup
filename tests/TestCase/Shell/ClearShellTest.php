<?php
namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Setup\Shell\ClearShell;

/**
 * Class TestCompletionStringOutput
 *
 */
class TestClearOutput extends ConsoleOutput {

	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}

/**
 */
class ClearShellTest extends TestCase {

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->out = new TestClearOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMock(
			'Setup\Shell\ClearShell',
			['in', 'err', '_stop'],
			[$io]
		);
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
		$output = $this->out->output;

		$this->assertContains('logs' . DS . 'fooo.log', $output);
	}

}
