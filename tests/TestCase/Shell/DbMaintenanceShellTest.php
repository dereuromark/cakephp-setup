<?php

namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Setup\Shell\DbMaintenanceShell;
use Setup\Shell\Traits\DbToolsTrait;
use Tools\TestSuite\ConsoleOutput;

class DbMaintenanceShellTest extends TestCase {

	use DbToolsTrait;

	/**
	 * @var \Setup\Shell\DbMaintenanceShell|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $Shell;

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
		$config = ConnectionManager::getConfig('test');
		if ((strpos($config['driver'], 'Mysql') === false)) {
			$this->skipIf(true, 'Only for MySQL (with MyISAM/InnoDB)');
		}

		$connection = $this->_getConnection('test');
		$script = 'DROP TABLE IF EXISTS `foo`; CREATE TABLE `foo` (title VARCHAR(255) NOT NULL);';
		$connection->execute($script);

		$this->Shell->expects($this->any())->method('in')
			->willReturn('Y');

		$this->Shell->runCommand(['encoding', '-d', '-v']);
		$output = $this->out->output();

		$expected = ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;';
		$this->assertContains($expected, $output, $output);
		$this->assertContains('Done :)', $output);

		$script = 'DROP TABLE IF EXISTS `foo`;';
		$connection->execute($script);
	}

	/**
	 * @return void
	 */
	public function testEngine() {
		$config = ConnectionManager::getConfig('test');
		if ((strpos($config['driver'], 'Mysql') === false)) {
			$this->skipIf(true, 'Only for MySQL (with MyISAM/InnoDB)');
		}

		$connection = $this->_getConnection('test');
		$script = 'DROP TABLE IF EXISTS `foo`; CREATE TABLE `foo` (title VARCHAR(255) NOT NULL) ENGINE = MYISAM;';
		$connection->execute($script);

		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->Shell->runCommand(['engine', 'InnoDB', '-d', '-v']);
		$output = $this->out->output();

		//$expected = ' ENGINE=InnoDB;';
		//$this->assertContains($expected, trim($output));
		$this->assertContains('Done :)', trim($output));

		$script = 'DROP TABLE IF EXISTS `foo`;';
		$connection->execute($script);
	}

	/**
	 * @return void
	 */
	public function testCleanup() {
		$config = ConnectionManager::getConfig('test');
		if ((strpos($config['driver'], 'Mysql') === false)) {
			$this->skipIf(true, 'Only for MySQL (with MyISAM/InnoDB)');
		}

		$connection = $this->_getConnection('test');
		$script = 'DROP TABLE IF EXISTS `_foo_`; CREATE TABLE `_foo_` (title VARCHAR(255) NOT NULL);';
		$connection->execute($script);

		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->Shell->runCommand(['cleanup', '-d', '-v']);
		$output = $this->out->output();

		$expected = ' tables found';
		$this->assertContains($expected, $output);
		$this->assertContains('Done :)', $output);
	}

	/**
	 * @return void
	 */
	public function testTablePrefix() {
		$config = ConnectionManager::getConfig('test');
		if ((strpos($config['driver'], 'Mysql') === false)) {
			$this->skipIf(true, 'Only for MySQL (with MyISAM/InnoDB)');
		}

		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->expectException(StopException::class);
		$this->expectExceptionMessage('Nothing to do...');

		$this->Shell->runCommand(['table_prefix', 'R', 'foooo_', '-d', '-v']);
	}

	/**
	 * @return void
	 */
	public function testTablePrefixAdd() {
		$config = ConnectionManager::getConfig('test');
		if ((strpos($config['driver'], 'Mysql') === false)) {
			$this->skipIf(true, 'Only for MySQL (with MyISAM/InnoDB)');
		}

		$connection = $this->_getConnection('test');
		$script = 'DROP TABLE IF EXISTS `foo_bars`; CREATE TABLE `foo_bars` (title VARCHAR(255) NOT NULL);';
		$connection->execute($script);

		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('Y'));

		$this->Shell->runCommand(['table_prefix', 'A', 'foo_', '-d', '-v']);

		$script = 'DROP TABLE IF EXISTS `foo_bars`;';
		$connection->execute($script);
	}

}
