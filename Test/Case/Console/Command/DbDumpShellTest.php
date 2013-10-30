<?php

App::uses('DbDumpShell', 'Setup.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class DbDumpShellTest extends MyCakeTestCase {

	public $DbDumpShell;

	public function setUp() {
		parent::setUp();
		$this->DbDumpShell = new TestDbDumpShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->DbDumpShell));
		$this->assertInstanceOf('DbDumpShell', $this->DbDumpShell);
	}

	public function testGetFiles() {
		$this->DbDumpShell->startup();

		$files = $this->DbDumpShell->getFiles();
		$this->out(returns($files));
		$this->assertTrue(is_array($files));
	}

	//TODO
}

class TestDbDumpShell extends DbDumpShell {

	public function getFiles() {
		return $this->_getFiles();
	}
}
