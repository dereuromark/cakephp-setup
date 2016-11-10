<?php
namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\Console\Shell;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Setup\Shell\DbMaintenanceShell;
use Tools\TestSuite\ConsoleOutput;

/**
 */
class DbMaintenanceShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\DbMaintenanceShell|\PHPUnit_Framework_MockObject_MockObject
	 */
	public $Shell;

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

		$this->Shell = $this->getMockBuilder(DbMaintenanceShell::class)
			->setMethods(['in', 'err', '_stop'])
			->setConstructorArgs([$io])
			->getMock();

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
	 * @return void
	 */
	public function testEncoding() {
		$config = ConnectionManager::config('test');
		if ((strpos($config['driver'], 'Mysql') === false)) {
			$this->skipIf(true, 'Only for MySQL (with MyISAM/InnoDB)');
		}

		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->Shell->runCommand(['encoding', '-d', '-v']);
		$output = $this->out->output();

		$expected = ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;';
		$this->assertContains($expected, $output, $output);
		$this->assertContains('Done :)', $output);
	}

	/**
	 * @return void
	 */
	public function testEngine() {
		$config = ConnectionManager::config('test');
		if ((strpos($config['driver'], 'Mysql') === false)) {
			$this->skipIf(true, 'Only for MySQL (with MyISAM/InnoDB)');
		}

		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->Shell->runCommand(['engine', 'InnoDB', '-d', '-v']);
		$output = $this->out->output();

		//$expected = ' ENGINE=InnoDB;';
		//$this->assertContains($expected, trim($output));
		$this->assertContains('Done :)', trim($output));
	}

	/**
	 * @return void
	 */
	public function testClean() {
		$config = ConnectionManager::config('test');
		if ((strpos($config['driver'], 'Mysql') === false)) {
			$this->skipIf(true, 'Only for MySQL (with MyISAM/InnoDB)');
		}

		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->Shell->runCommand(['cleanup', '-d', '-v']);
		$output = $this->out->output();

		//debug($output);
		$expected = ' tables found';
		$this->assertContains($expected, $output);
		$this->assertContains('Done :)', $output);
	}

	/**
	 * @return void
	 */
	public function testTablePrefix() {
		$config = ConnectionManager::config('test');
		if ((strpos($config['driver'], 'Mysql') === false)) {
			$this->skipIf(true, 'Only for MySQL (with MyISAM/InnoDB)');
		}

		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->expectException(StopException::class);
		$this->expectExceptionMessage('Nothing to do...');

		$this->Shell->runCommand(['table_prefix', 'R', 'foo_', '-d', '-v']);
	}

	/**
	 * @return void
	 */
	public function testTablePrefixAdd() {
		$config = ConnectionManager::config('test');
		if ((strpos($config['driver'], 'Mysql') === false)) {
			$this->skipIf(true, 'Only for MySQL (with MyISAM/InnoDB)');
		}

		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->expectException(StopException::class);
		$this->expectExceptionMessage('Nothing to do...');

		$this->Shell->runCommand(['table_prefix', 'A', 'foo_', '-d', '-v']);
	}

}
