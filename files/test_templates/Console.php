<?php

App::uses('{class}', '{package}');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('TestConsoleOutput', 'Tools.TestSuite');

/**
 * {class} test case
 */
class {class}Test extends MyCakeTestCase {

	public ${class};

	public function setUp() {
		parent::setUp();
		{init}
	}

	public function testObject() {
		$this->assertInstanceOf('{class}', $this->{class});
	}

	{body}

}
