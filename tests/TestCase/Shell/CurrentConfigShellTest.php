<?php
namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\TestSuite\TestCase;
use Setup\Shell\CurrentConfigShell;

/**
 * Class TestCompletionStringOutput
 */
class TestCurrentConfigOutput extends ConsoleOutput {

	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}

/**
 * CurrentConfig shell test
 */
class CurrentConfigShellTest extends TestCase {

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

class TestCurrentConfigShell extends CurrentConfigShell {
}
