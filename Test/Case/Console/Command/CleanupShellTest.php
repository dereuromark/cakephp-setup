<?php

App::uses('CleanupShell', 'Setup.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CleanupShellTest extends MyCakeTestCase {

	public $CleanupShell;

	public function setUp() {
		parent::setUp();

		$this->CleanupShell = new TestCleanupShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->CleanupShell));
		$this->assertInstanceOf('CleanupShell', $this->CleanupShell);
	}

}

class TestCleanupShell extends CleanupShell {

}
