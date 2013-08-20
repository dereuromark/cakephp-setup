<?php

App::uses('CurrentConfigShell', 'Setup.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CurrentConfigShellTest extends MyCakeTestCase {

	public $CurrentConfigShell;

	public function setUp() {
		parent::setUp();

		$this->CurrentConfigShell = new TestCurrentConfigShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->CurrentConfigShell));
		$this->assertIsA($this->CurrentConfigShell, 'CurrentConfigShell');
	}

}

class TestCurrentConfigShell extends CurrentConfigShell {

}
