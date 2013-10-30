<?php

App::uses('SetupLib', 'Setup.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class SetupLibTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();
		$this->SetupLib = new SetupLib();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testObject() {
		$this->assertInstanceOf('SetupLib', $this->SetupLib);
	}

	public function testX() {
		//TODO
	}

}
