<?php

/**
 */
class AllCakeTests extends PHPUnit_Framework_TestSuite {

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

		$suite->addTestFile($path . 'BasicsTest.php');
		$suite->addTestFile($path . 'AllConsoleTest.php');
		$suite->addTestFile($path . 'AllBehaviorsTest.php');
		$suite->addTestFile($path . 'AllCacheTest.php');
		$suite->addTestFile($path . 'AllComponentsTest.php');
		$suite->addTestFile($path . 'AllConfigureTest.php');
		$suite->addTestFile($path . 'AllCoreTest.php');
		$suite->addTestFile($path . 'AllControllerTest.php');
		$suite->addTestFile($path . 'AllDatabaseTest.php');
		$suite->addTestFile($path . 'AllErrorTest.php');
		$suite->addTestFile($path . 'AllHelpersTest.php');
		$suite->addTestFile($path . 'AllLogTest.php');
		$suite->addTestFile($path . 'AllI18nTest.php');
		$suite->addTestFile($path . 'AllModelTest.php');
		$suite->addTestFile($path . 'AllRoutingTest.php');
		$suite->addTestFile($path . 'AllNetworkTest.php');
		$suite->addTestFile($path . 'AllTestSuiteTest.php');;
		$suite->addTestFile($path . 'AllUtilityTest.php');
		$suite->addTestFile($path . 'AllViewTest.php');
		return $suite;
	}

}
