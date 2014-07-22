<?php
App::uses('SetupAppModel', 'Setup.Model');
if (!defined('BACKUPS')) {
	define("BACKUPS", APP . 'Config' . DS . 'backups' . DS);
}

/**
 * //deprecated
 * create a backup folder with timestamp containing separate DB table files
 * TODO: create packed version as zip folder?
 */
class Dump extends SetupAppModel {

	public $useTable = false;

	/**
	 * @return bool Success
	 */
	public function sqlDump() {
		//var_dump(exec(CAKE.'console/cake schema dump -write filename.sql'));
		$tables = $this->query("SHOW TABLES");
		///Configure::load('Database');
		//var_dump(Configure::read('debug'));
		//var_dump($this->database);
		//$dbConfig = $this->getDataSource()->config;
		//var_dump($dbConfig['database']);
		$time = $this->currentTime();
		if (!class_exists('Folder')) {
			App::uses('Folder', 'Utility');
		}
		$backup = new Folder(BACKUPS . "sqlBackup" . $time, true);

		$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($tables)); //ARRAY FLATTENER!!! PHP SPL KUTUPHANESI!

		$path = BACKUPS . "sqlBackup" . $time . DS;
		$path = str_replace('\\', '/', $path);

		foreach ($it as $v) {
			$theQuery = "SELECT * INTO OUTFILE '" . $path . $v . ".csv' FROM " . $v . ";";
			$this->query($theQuery);
		}
		//return $this->query($sql);
		return true;
	}

	/**
	 * @return bool Success
	 */
	public function sqlRecover($time, $customTables = array()) {
		$tables = $this->query("SHOW TABLES");
		///Configure::load('Database');
		//var_dump(Configure::read('debug'));
		//var_dump($this->database);
		//$dbConfig = $this->getDataSource()->config;
		//var_dump($dbConfig['database']);

		$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($tables)); //ARRAY FLATTENER!!! PHP SPL KUTUPHANESI!

		$path = BACKUPS . "sqlBackup" . $time . DS;
		$path = str_replace('\\', '/', $path);

		foreach ($it as $v) {
			if (empty($customTables) || in_array($v, $customTables)) {
				$truncate = "TRUNCATE TABLE " . $v . ";";
				$theQuery = "LOAD DATA INFILE '" . $path . $v . ".csv' INTO TABLE " . $v . ";";
				$this->query($truncate);
				$this->query($theQuery);
			}
		}

		return true;
	}

	public function sqlPack($time) {
	}

	public function sqlUnpack($time) {
	}

	public function currentTime() {
		return date("Ymdhis");
	}

}
