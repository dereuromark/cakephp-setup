<?php

/**
 * SQL DB Backup
 */
class BackupLib {

	protected $_pathFiles = "files/";

	protected $_pathBackup = "/tmp/backup";

	protected $_fileNameSql = "backup.sql";

	protected $_fileNameTar;

	protected $Model = null;

	public $config = null;

	public function __construct(Model $Model) {
		$this->Model = $Model;
		$this->_getConnection();
	}

	public function _getConnection() {
		if ($this->config !== null) {
			return;
		}
		$config = $this->Model->useDbConfig;
		$db = ConnectionManager::getDataSource($config);
		$this->config = $db->config;
	}

	public function listTables($onlyOwn = false) {
		$query = 'SHOW TABLES FROM ' . $this->config['database'];
		if ($onlyOwn && !empty($this->config['prefix'])) {
			$query .= ' LIKE \'' . $this->config['prefix'] . '%\'';
		}
		$tables = $this->Model->query($query);
		if (!$tables) {
			return array();
		}
		//$tables = $this->Configuration->query('SHOW TABLES FROM '.$database .' LIKE \''.$this->Configuration->tablePrefix.'%\'');
		$key = 'Tables_in_' . $this->config['database'];
		if ($onlyOwn && !empty($this->config['prefix'])) {
			$key .= ' (' . $this->config['prefix'] . '%)';
		}
		return Set::extract('/TABLE_NAMES/' . $key, $tables);
	}

	public function exportSql($path, $tables = array(), $options = array()) {
		$this->_getConnection();
		$res = shell_exec("mysqldump --opt --password=" . $this->config['password'] . " --user=" . $this->config['login'] . " " . $this->config['database'] . " > " . $path);

		die(returns($res));
	}

	public function importSql($path, $options = array()) {
		$this->_getConnection();
		$res = shell_exec("mysqldump --password=" . $this->config['password'] . " --user=" . $this->config['login'] . " " . $this->config['database'] . " < " . $path);
	}

/* other */

	/**
	 * Genera Backup de la BD y almacena carpeta files, donde se subieron archivos
	 *
	 * @return void
	 */
	public function makeBackup() {

		//Copia los ficheros a guardar en carpeta temporal
		//$this->_backupFiles();

		//genera fichero SQL con BACKUP de la BD
		$this->_backupDB();

		//Empaquetamos ambas cosas
		$this->_backupTar();

		//Descargamos backup
		$this->_backupDownload();
	}

	/**
	 * Realiza copia de la carpeta $_path_files en $_path_backup
	 *
	 * @return void
	 */
	protected function _backupFiles() {
		//Generamos carpeta donde se almacenaran los archivos
		if (!is_dir($this->_pathBackup)) {
			mkdir($this->_pathBackup, 0777);
		}
		//Copiamos el contenido de la carpeta files
		shell_exec("cp -r " . ROOT . DS . APP_DIR . DS . WEBROOT_DIR . DS . $this->_pathFiles . "* " . $this->_pathBackup);
	}

	/**
	 * Genera Backup de la BD (mysqldump)
	 *
	 * @return void
	 */
	protected function _backupDB() {
		$this->exportSql($this->_pathBackup . DS . $this->_fileNameSql);
	}

	/**
	 * Empaqueta todos los ficheros
	 *
	 * @return void
	 */
	protected function _backupTar() {
		$this->_fileNameTar = "backup-" . date("dmYHis") . ".tar.gz";
		shell_exec("tar -cvzf /tmp/" . $this->_fileNameTar . " " . $this->_pathBackup);
	}

	/**
	 * Prepara para descargar los ficheros
	 *
	 * @return void
	 */
	protected function _backupDownload() {
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"" . $this->_fileNameTar . "\"");
		$fp = fopen("/tmp/" . $this->_fileNameTar, "r");
		fpassthru($fp);
		exit();
	}
}
