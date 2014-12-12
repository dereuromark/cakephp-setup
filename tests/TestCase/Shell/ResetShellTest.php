<?php
namespace Setup\Test\TestCase\Shell;

use Setup\Shell\ResetShell;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Class TestCompletionStringOutput
 */
class TestResetOutput extends ConsoleOutput {

	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}

class ResetShellTest extends TestCase {

	public $Shell;

	public $fixtures = array('plugin.Setup.Users');

	public function setUp() {
		parent::setUp();

		$this->out = new TestResetOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMock(
			'Setup\Shell\ResetShell',
			['in', 'err', '_stop'],
			[$io]
		);
	}

	/**
	 * ResetShellTest::testEmail()
	 *
	 * @return void
	 */
	public function testEmail() {
		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('example@example.de'));

		$this->Shell->runCommand(['email']);
		$output = $this->out->output;

		$expected = '0 emails resetted';
		$this->assertTextContains($expected, (string)$output);
	}

	/**
	 * ResetShellTest::testEmailQuick()
	 *
	 * @return void
	 */
	public function testEmailQuick() {
		$this->Shell->runCommand(['email', 'example@example.de']);
		$output = $this->out->output;

		$expected = '0 emails resetted';
		$this->assertTextContains($expected, (string)$output);
	}

	/**
	 * ResetShellTest::testPwd()
	 *
	 * @return void
	 */
	public function testPwd() {
		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('123'));

		$this->Shell->runCommand(['pwd']);
		$output = $this->out->output;

		$expected = '0 pwds resetted';
		$this->assertTextContains($expected, (string)$output);
	}

	/**
	 * ResetShellTest::testPwd()
	 *
	 * @return void
	 */
	public function testPwdQuick() {
		$this->Shell->runCommand(['pwd', '123']);
		$output = $this->out->output;

		$expected = '0 pwds resetted';
		$this->assertTextContains($expected, (string)$output);
	}

}

class TestResetShell extends ResetShell {

}
