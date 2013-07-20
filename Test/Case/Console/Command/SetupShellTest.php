<?php

App::uses('SetupShell', 'Setup.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class SetupShellTest extends MyCakeTestCase {

	public $SetupShell;

	public function setUp() {
		$this->SetupShell = new TestSetupShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->SetupShell));
		$this->assertIsA($this->SetupShell, 'SetupShell');
	}

}


class TestSetupShell extends SetupShell {

}
