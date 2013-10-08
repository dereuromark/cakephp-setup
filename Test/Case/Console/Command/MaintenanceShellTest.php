<?php

App::uses('MaintenanceShell', 'Setup.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class MaintenanceShellTest extends MyCakeTestCase {

	public $MaintenanceShell;

	public function setUp() {
		parent::setUp();
		$this->MaintenanceShell = new TestMaintenanceShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->MaintenanceShell));
		$this->assertInstanceOf('MaintenanceShell', $this->MaintenanceShell);
	}

}

class TestMaintenanceShell extends MaintenanceShell {

}
