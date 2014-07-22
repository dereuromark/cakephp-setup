<?php

App::uses('ClearShell', 'Setup.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class ClearShellTest extends MyCakeTestCase {

	public $ClearShell;

	public function setUp() {
		parent::setUp();

		$this->ClearShell = new TestClearShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->ClearShell));
		$this->assertInstanceOf('ClearShell', $this->ClearShell);
	}

}

class TestClearShell extends ClearShell {

}
