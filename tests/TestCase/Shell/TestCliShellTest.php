<?php
namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
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
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.fullBaseUrl', 'example.local');

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

		Configure::delete('App.fullBaseUrl');
	}

	/**
	 * Test clean command
	 *
	 * @return void
	 */
	public function testRouter() {
		$this->Shell->runCommand(['router']);
		$output = $this->out->output();

		$this->assertTextContains('Router::url(\'/\')', $output, print_r($output, true));
		$this->assertTextContains('example.local/test', $output, print_r($output, true));
	}

	/**
	 * Test clean command
	 *
	 * @return void
	 */
	public function testRouterPrefix() {
		$this->Shell->runCommand(['router', '-x', 'admin']);
		$output = $this->out->output();

		$this->assertTextContains('Router::url([\'controller\' => \'Test\', \'prefix\' => \'admin\'], true)', $output, print_r($output, true));
		$this->assertTextContains('/admin/test', $output, print_r($output, true));
	}

}
