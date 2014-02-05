<?php

App::uses('TestsShell', 'Setup.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class TestsShellTest extends MyCakeTestCase {

	public $TestsShell;

	public function setUp() {
		parent::setUp();

		$this->TestsShell = new TestsShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->TestsShell));
		$this->assertInstanceOf('TestsShell', $this->TestsShell);
	}

	//TODO
}
