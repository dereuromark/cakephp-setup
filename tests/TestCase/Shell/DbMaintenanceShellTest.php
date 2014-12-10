<?php
namespace Setup\Test\TestCase\Shell;

use Setup\Shell\DbMaintenanceShell;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Class TestCompletionStringOutput
 *
 */
class TestDbMaintenanceOutput extends ConsoleOutput {

	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}

/**
 */
class DbMaintenanceShellTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->out = new TestDbMaintenanceOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMock(
			'Setup\Shell\DbMaintenanceShell',
			['in', 'err', '_stop'],
			[$io]
		);

		if (!is_dir(TMP . 'DbMaintenance')) {
			mkdir(TMP . 'DbMaintenance', 0770, true);
		}
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
	public function testEncoding() {
		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->Shell->runCommand(['encoding', '-d', '-v']);
		$output = $this->out->output;

		//debug($output);
		$expected = ' CHARACTER SET utf8 COLLATE utf8_unicode_ci;';
		$this->assertContains($expected, $output);
		$this->assertContains('Done :)', $output);
	}

/**
 * Test clean command
 *
 * @return void
 */
	public function testEngine() {
		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->Shell->runCommand(['engine', 'InnoDB', '-d', '-v']);
		$output = $this->out->output;

		//debug($output);
		$expected = ' ENGINE=InnoDB;';
		$this->assertContains($expected, $output);
		$this->assertContains('Done :)', $output);
	}

/**
 * Test clean command
 *
 * @return void
 */
	public function testClean() {
		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->Shell->runCommand(['cleanup', '-d', '-v']);
		$output = $this->out->output;

		//debug($output);
		$expected = ' tables found';
		$this->assertContains($expected, $output);
		$this->assertContains('Done :)', $output);
	}

}
