<?php

App::uses('{class}', '{package}');
App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * {class} test case
 */
class {class}Test extends MyCakeTestCase {

	public $Controller;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		{init}
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Controller);
		ClassRegistry::flush();
	}

	/**
	 * testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->assertInstanceOf('{class}', $this->Controller->{class});
	}

	{body}

}
