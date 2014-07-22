<?php
App::uses('SetupAppController', 'Setup.Controller');

/**
 */
class TestInputController extends SetupAppController {

	public $components = array();

	public function beforeFilter() {
		parent::beforeFilter();

		App::uses('DebugLib', 'Setup.Lib');
		$this->DebugLib = new DebugLib();

		if (isset($this->Auth)) {
			$this->Auth->allow('admin_index');
		}
	}

	public function admin_index() {
		$data = $_SERVER;
		CakeLog::write('input', var_export($data, true));

		$data = $_REQUEST;
		CakeLog::write('input', var_export($data, true));

		$data = $_POST;
		CakeLog::write('input', var_export($data, true));

		$data = $this->request->data;
		CakeLog::write('input', var_export($data, true));

		die(json_encode(array('testdone' => true)));
	}

}
