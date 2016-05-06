<?php
namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * Class TestCompletionStringOutput
 */
class TestUserOutput extends ConsoleOutput {

	/**
	 * @var string
	 */
	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}

class UserShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\UserShell
	 */
	public $Shell;

	/**
	 * @var array
	 */
	public $fixtures = ['plugin.Setup.Users'];

	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->out = new TestUserOutput();
		$this->err = new TestUserOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$this->Shell = $this->getMock(
			'Setup\Shell\UserShell',
			['in', '_stop'],
			[$io]
		);

	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * UserShellTest::testEmail()
	 *
	 * @return void
	 */
	public function testUser() {
		$this->Shell->expects($this->at(0))->method('in')
			->will($this->returnValue('y'));
		$this->Shell->expects($this->at(1))->method('in')
			->will($this->returnValue('example@example.de'));
		$this->Shell->expects($this->at(2))->method('in')
			->will($this->returnValue('y'));

		$this->Shell->runCommand(['create', 'example', '123']);

		$output = $this->err->output;
		$this->assertEmpty($output, $output);

		$output = $this->out->output;
		$expected = '[username] => example';
		$this->assertTextContains($expected, (string)$output);
	}

	/**
	 * UserShellTest::testUserInteractive()
	 *
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
