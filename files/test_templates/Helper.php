<?php

App::uses('{class}', '{package}');
App::uses('View', 'View');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * {class} test case
 */
class {class}Test extends MyCakeTestCase {

	public ${class};

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		{init}
	}

	/**
	 * testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->assertInstanceOf('{class}', $this->{class});
	}

	{body}

}
