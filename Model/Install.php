<?php
App::uses('InstallLib', 'Setup.Lib');
App::uses('Model', 'Model');

class Install extends Model {

	public $useTable = false;

	/**
	 * TODO: validate?
	 */
	public function createDatabaseFile($data) {
		$params = $data[$this->alias];

		return InstallLib::writeTemplate($params);
	}

}
