<?php
namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\TestSuite\TestCase;

/**
 * CurrentConfig shell test
 */
class CurrentConfigShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\CurrentConfigShell
	 */
	public $Shell;

	public function setUp() {
		parent::setUp();

		$this->out = new TestCurrentConfigOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMock(
			'Setup\Shell\CurrentConfigShell',
			['in', 'err', '_stop'],
			[$io]
		);
	}

	public function testMain() {
		$this->Shell->runCommand(['clean', TMP]);
		$output = $this->out->output;

		$this->assertContains('[driver]', $output);
		$this->assertContains('[className]', $output);
	}

}

/**
 * Class TestCompletionStringOutput
 */
class TestCurrentConfigOutput extends ConsoleOutput {

	/**
	 * @var string
	 */
	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}
