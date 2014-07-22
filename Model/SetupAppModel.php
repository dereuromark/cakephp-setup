<?php
App::uses('AppModel', 'Model');
class SetupAppModel extends AppModel {

	public function __construct($id = false, $table = null, $ds = null) {
		if (($table = Configure::read('Configuration.table')) !== null) {
			$this->useTable = $table;
		}
		parent::__construct($id, $table, $ds);
	}

}
