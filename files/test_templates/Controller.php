<?php

App::uses('IntegrationTestCase', 'Tools.TestSuite');

/**
 * {class} test case
 */
class {class}Test extends IntegrationTestCase {

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		ClassRegistry::flush();
	}

	{body}

}
