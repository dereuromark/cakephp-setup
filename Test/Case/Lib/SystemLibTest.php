<?php
App::uses('SystemLib', 'Setup.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class SystemLibTest extends MyCakeTestCase {

	public $Config;

	public function setUp() {
		parent::setUp();
		$this->SystemLib = new SystemLib();
	}

	public function testSystemLib() {
		$this->assertInstanceOf('SystemLib', $this->SystemLib);
	}

	public function testDiskSpace() {
		$this->out('<h3>DiskSpace</h3>', true);
		if (WINDOWS) {
			$this->out('<div>USE LINUX TO FULLY TEST (only available on linux!)</div>', true);
		}

		$rootPath = ROOT . DS . APP_DIR;

		$is = $this->SystemLib->diskSpace($rootPath);
		$this->debug($is);
		$this->assertTrue(is_array($is));
		if (WINDOWS) {
			$this->assertTrue(empty($is));
		} else {
			$this->assertTrue(!empty($is));
		}

		$is = $this->SystemLib->freeDiskSpace();
		$this->debug($is);
		$this->assertTrue(is_array($is));
		if (WINDOWS) {
			$this->assertTrue(empty($is['total']));
		} else {
			$this->assertTrue(!empty($is['total']));
		}
	}

	public function testTree() {
	}

}
