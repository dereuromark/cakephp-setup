<?php
App::uses('SqlLogComponent', 'Setup.Controller/Component');
App::uses('Controller', 'Controller');

class SqlLogComponentTest extends CakeTestCase {

	public $fixtures = array('core.comment');

	public function setUp() {
		parent::setUp();

		$this->Controller = new SqlLogTestController();
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();
		$this->Controller->SqlLog = $this->Controller->TestSqlLog;
		$this->Controller->SqlLog->blackHoleCallback = 'fail';
	}

	public function tearDown() {
		unset($this->Controller->SqlLog);
		unset($this->Controller->Component);
		unset($this->Controller);

		parent::tearDown();
	}

	/**
	 * test
	 */
	public function testLog() {
		if (file_exists(LOGS . 'sql.log')) {
			unlink(LOGS . 'sql.log');
		}
		Configure::write('System.sqlLog', 1);

		$Model = ClassRegistry::init('Comment');
		$Model->find('first');

		$this->Controller->beforeRender($this->Controller);
		$this->Controller->SqlLog->beforeRender($this->Controller);

		$this->assertTrue(file_exists(LOGS . 'sql.log'));

		$this->assertTrue(filemtime(LOGS . 'sql.log') >= (time() - MINUTE));
	}

}

/*** other files ***/

/**
 * Short description for class.
 *
 */
class TestSqlLogComponent extends SqlLogComponent {

}
/**
 * Short description for class.
 *
 */
class SqlLogTestController extends Controller {

	public $components = array('TestSqlLog');

	/**
	 * Failed property
	 *
	 * @var bool
	 */
	public $failed = false;

	/**
	 * Used for keeping track of headers in test
	 *
	 * @var array
	 */
	public $testHeaders = array();

	/**
	 * Fail method
	 *
	 * @return void
	 */
	public function fail() {
		$this->failed = true;
	}

	/**
	 * Redirect method
	 *
	 * @param mixed $option
	 * @param mixed $code
	 * @param mixed $exit
	 * @return void
	 */
	public function redirect($option, $code, $exit) {
		return $code;
	}

	/**
	 * Conveinence method for header()
	 *
	 * @param string $status
	 * @return void
	 */
	public function header($status) {
		$this->testHeaders[] = $status;
	}

}
