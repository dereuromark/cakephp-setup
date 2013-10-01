<?php

App::uses('SetupComponent', 'Setup.Controller/Component');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('Controller', 'Controller');

class SetupComponentTest extends MyCakeTestCase {

	public function setUp() {
		$this->Setup = new SetupComponent(new ComponentCollection);
	}

	public function tearDown() {

	}

	public function testObject() {
		$this->assertInstanceOf('SetupComponent', $this->Setup);
	}

	public function testX() {
		//TODO
	}

}
