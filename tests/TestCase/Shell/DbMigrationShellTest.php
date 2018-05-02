<?php
namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Setup\Shell\DbMigrationShell;
use Tools\TestSuite\ConsoleOutput;

/**
 */
class DbMigrationShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\DbMigrationShell|\PHPUnit_Framework_MockObject_MockObject
	 */
	public $Shell;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $err;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$this->Shell = $this->getMockBuilder(DbMigrationShell::class)
			->setMethods(['in', 'err', '_stop'])
			->setConstructorArgs([$io])
			->getMock();

		if (!is_dir(TMP . 'DbMigration')) {
			mkdir(TMP . 'DbMigration', 0770, true);
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
	 * @return void
	 */
	public function testNulls() {
		$config = ConnectionManager::getConfig('test');
		if ((strpos($config['driver'], 'Mysql') === false)) {
			$this->skipIf(true, 'Only for MySQL (with MyISAM/InnoDB)');
		}

		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->Shell->runCommand(['nulls', '-d', '-v']);
		$output = $this->out->output();

		$expected = 'Nothing to do :)';
		$this->assertContains($expected, $output, $output);
	}

}
