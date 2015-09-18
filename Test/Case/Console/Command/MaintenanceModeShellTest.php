<?php

App::uses('MaintenanceModeShell', 'Setup.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class MaintenanceModeShellTest extends MyCakeTestCase {

	public $MaintenanceModeShell;

	public function setUp() {
		parent::setUp();
		$this->MaintenanceModeShell = new TestMaintenanceModeShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->MaintenanceModeShell));
		$this->assertInstanceOf('MaintenanceModeShell', $this->MaintenanceModeShell);
	}

}

class TestMaintenanceModeShell extends MaintenanceModeShell {
}
