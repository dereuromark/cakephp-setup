<?php

/**
 */
class AllCakeCoreTests extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite define the tests for this suite
	 *
	 * @return void
	 */
	public static function suite() {
		ini_set('memory_limit', '512M'); # needs around 300M usually

		//$suite->addTestFile(CORE_TEST_CASES . DS . 'AllTestsTest.php');
		$suite = new PHPUnit_Framework_TestSuite('All Tests');
		ini_set('memory_limit', '512M');
		$path = CORE_TEST_CASES . DS;

		$suite->addTestFile($path . 'AllTestsTest');
		return $suite;
	}

}
