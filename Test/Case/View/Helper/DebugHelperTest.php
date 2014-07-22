<?php
App::uses('DebugHelper', 'Setup.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('View', 'View');
App::uses('Controller', 'Controller');

class DebugHelperTest extends MyCakeTestCase {

	public $fixtures = array('core.cake_session');

	public $Debug;

	public function setUp() {
		parent::setUp();

		$this->Debug = new DebugHelper(new View(new Controller(new CakeRequest, new CakeResponse)));
	}

	public function tearDown() {
		unset($this->Debug);

		parent::tearDown();
	}

	public function testObject() {
		$this->assertInstanceOf('DebugHelper', $this->Debug);
	}

	public function testAdd() {
		$is = $this->Debug->add('1', 'x', 'y');
		$this->assertTrue($is);
	}

	public function testShow() {
		$is = $this->Debug->show();
		$this->debug($is);
	}

}
