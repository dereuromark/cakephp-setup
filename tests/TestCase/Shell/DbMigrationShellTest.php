<?php

namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Setup\Shell\DbMigrationShell;
use Setup\TestSuite\DriverSkipTrait;
use Shim\TestSuite\ConsoleOutput;

class DbMigrationShellTest extends TestCase {

	use DriverSkipTrait;

	/**
	 * @var \Setup\Shell\DbMigrationShell|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $Shell;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $err;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$this->Shell = $this->getMockBuilder(DbMigrationShell::class)
			->onlyMethods(['in', '_stop'])
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
	public function tearDown(): void {
		parent::tearDown();
		unset($this->Shell);
	}

	/**
	 * @return void
	 */
	public function testNulls() {
		$this->skipIfNotDriver('Mysql', 'Only for MySQL (with MyISAM/InnoDB)');

		$this->Shell->runCommand(['nulls', '-d', '-v']);
		$output = $this->out->output();

		$expected = 'Nothing to do :)';
		$this->assertStringContainsString($expected, $output, $output);
	}

}
