<?php
App::uses('BaseConfig', 'Setup.Lib');
if (!defined('HTTP_HOST')) {
	define('HTTP_HOST', $_SERVER['HTTP_HOST']);
}
define('IS_CLI', php_sapi_name() === 'cli' && empty($_SERVER['REMOTE_ADDR']));

class BaseConfigTest extends CakeTestCase {

	public $Config;

	public function setUp() {
		parent::setUp();
		$this->Config = new TEST_DATABASE_CONFIG();
	}

	public function testConfig() {
		$this->assertInstanceOf('TEST_DATABASE_CONFIG', $this->Config);
	}

	public function testGetEnvironmentName() {
		$is = $this->Config->getEnvironmentName();
		$this->assertEquals('default', $is);
	}

	public function testCurrent() {
		$this->skipIf(IS_CLI);

		$is = $this->Config->current();
		$this->assertTrue(!empty($is) && in_array(HTTP_HOST, $is['environment']));

		$is = $this->Config->current(true);
		$this->assertTrue(!empty($is));
		$this->assertEquals('local', $is);
	}

	public function testCurrentWithCLI() {
		$this->skipIf(!IS_CLI);

		$this->Config = new TEST_DATABASE_CONFIG();

		$is = $this->Config->current();
		$this->assertTrue(!empty($is) && in_array(APP, $is['path']));

		$is = $this->Config->current(true);
		$this->assertTrue(!empty($is));
		$this->assertEquals('cli', $is);
	}

}

class TEST_DATABASE_CONFIG extends BaseConfig {

	public $default = array(
		'name' => 'local',
		'environment' => array(HTTP_HOST),
	);

	public $cli = array(
		'name' => 'cli',
		'path' => array(APP),
	);

	public $test = array(
		'name' => 'testconfig',
		'merge' => true
	);
}
