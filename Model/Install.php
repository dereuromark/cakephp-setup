<?php
App::uses('InstallLib', 'Setup.Lib');
App::uses('Model', 'Model');

class Install extends Model {
	
	public $useTable = false;
	
	/**
	 * TODO: validate?
	 * 2012-04-14 ms
	 */
	public function createDatabaseFile($data) {
		$params = $data[$this->alias];
		
		return InstallLib::writeTemplate($params);
	}
	
}