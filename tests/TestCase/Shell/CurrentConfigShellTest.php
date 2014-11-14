<?php
namespace Setup\Test\TestCase\Shell;

use Setup\Shell\CurrentConfigShell;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;


class CurrentConfigShellTest extends TestCase {

	public $CurrentConfigShell;

	public function setUp() {
		parent::setUp();

		$this->CurrentConfigShell = new TestCurrentConfigShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->CurrentConfigShell));
		$this->assertInstanceOf('Setup\Shell\CurrentConfigShell', $this->CurrentConfigShell);
	}

}

class TestCurrentConfigShell extends CurrentConfigShell {

}
