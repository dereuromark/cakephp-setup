<?php
/**
 * Group test - Setup
 */
class AllSetupTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Setup plugin tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Controller');
		$Suite->addTestDirectory($path . DS . 'Controller' . DS . 'Component');
		$Suite->addTestDirectory($path . DS . 'View' . DS . 'Helper');
		$Suite->addTestDirectory($path . DS . 'Console' . DS . 'Command');
		$Suite->addTestDirectory($path . DS . 'Lib');
		return $Suite;
	}
}
