<?php

App::uses('AppModel', 'Model');
App::uses('BackupLib', 'Setup.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class Test extends AppModel {

	public $useTable = false;

}

class BackupLibTest extends MyCakeTestCase {

	public $BackupLib = null;

	public $Model = null;

	public function setUp() {
		parent::setUp();
		$this->Model = new Test();
		$this->BackupLib = new BackupLib($this->Model);
	}

	public function testCheckConfig() {
		$res = $this->BackupLib->config;
		pr($res);
		$this->assertTrue(!empty($res));
	}

	public function testListTables() {
		$res = $this->BackupLib->listTables();
		pr($res);
		$this->assertTrue(!empty($res));
	}

}
