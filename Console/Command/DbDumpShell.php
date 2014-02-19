<?php
App::uses('AppShell', 'Console/Command');
App::uses('Inflector', 'Utility');
App::uses('ConnectionManager', 'Model');
App::uses('NumberLib', 'Tools.Utility');

if (!defined('BACKUPS')) {
	define('BACKUPS', APP . 'files' . DS . 'backup' . DS);
}
if (!defined('CHMOD_PUBLIC')) {
	define('CHMOD_PUBLIC', 0770);
}
if (!defined('WINDOWS')) {
	if (substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

/**
 * A Shell to easily create and restore DB dumps
 * - Tested on windows and linux
 * - Uses settings from database.php
 * - Custom backup path possible
 * - Custom tables possible (prompted or manually passed)
 * - Clear and cleanup
 * - Could be used in a cronjob environment (e.g.: backup every 12 hours)
 *
 * @version 1.0
 * @author Mark Scherer
 * @cakephp 2.0
 * @licence MIT
 */
class DbDumpShell extends AppShell {

	public $tasks = array('WriteSql');

	public function startup() {
		parent::startup();

		if (!is_dir(BACKUPS)) {
			mkdir(BACKUPS, CHMOD_PUBLIC, true);
		}
	}

	/**
	 * Create an SQL dump file in the backup folder.
	 *
	 * Options
	 * -?: TODO drop tables
	 * -t: specific tables
	 * -c: compress using gzip
	 *
	 * @return void
	 */
	public function create() {
		$db = ConnectionManager::getDataSource('default');
		$usePrefix = empty($db->config['prefix']) ? '' : $db->config['prefix'];
		$file = $this->_path() . 'dbdump_' . date("Y-m-d--H-i-s");

		$options = array(
			'--user=' . $db->config['login'],
			'--password=' . $db->config['password'],
			'--default-character-set=' . $db->config['encoding'],
			'--host=' . $db->config['host'],
			'--databases ' . $db->config['database'],
		);
		$sources = $db->listSources();
		if (array_key_exists('tables', $this->params) && empty($this->params['tables'])) {
			// prompt for tables
			foreach ($sources as $key => $source) {
				$this->out('[' . $key . '] ' . $source);
			}
			$tables = $this->in('What tables (separated by comma without spaces)', null, null);
			$tables = explode(',', $tables);
			$tableList = array();
			foreach ($tables as $table) {
				if (isset($sources[intval($table)])) {
					$tableList[] = $sources[intval($table)];
				}
			}
			$options[] = '--tables ' . implode(' ', $tableList);
			$file .= '_custom';

		} elseif (!empty($this->params['tables'])) {
			$sources = explode(',', $this->params['tables']);
			foreach ($sources as $key => $val) {
				$sources[$key] = $usePrefix . $val;
			}
			$options[] = '--tables ' . implode(' ', $sources);
			$file .= '_custom';
		} elseif ($usePrefix) {
			foreach ($sources as $key => $source) {
				if (strpos($source, $usePrefix) !== 0) {
					unset($sources[$key]);
				}
			}
			$options[] = '--tables ' . implode(' ', $sources);
			$file .= '_' . rtrim($usePrefix, '_');
		}
		$file .= '.sql';
		if (!empty($this->params['compress'])) {
			$options[] = '| gzip';
			$file .= '.gz';
		}
		$options[] = '> ' . $file;

		$this->out('Backup will be written to:');
		$this->out(' - ' . $this->_path());
		$looksGood = $this->in(__d('cake_console', 'Look okay?'), array('y', 'n'), 'y');
		if ($looksGood !== 'y') {
			return $this->error('Aborted!');
		}

		if ($this->_create($options)) {
			$this->out('Done :)');
		}
	}

	protected function _create($options) {
		$command = $this->_command('mysqldump');
		if (!empty($options)) {
			$command .= ' ' . implode(' ', $options);
		}
		if (!empty($this->params['dry-run'])) {
			$this->out($command);
			$ret = 0;
		} else {
			exec($command, $output, $ret);
		}
		return $ret === 0;
	}

	protected function _command($command) {
		$path = '';
		switch ($command) {
			case 'gzip':
			case 'gunzip':
				$path = Configure::read('Cli.gitPath');
				break;
			default:
				$path = Configure::read('Cli.mysqlPath');
		}
		return (WINDOWS && $path ? $path : '') . $command;
	}

	protected function _path() {
		$path = BACKUPS;
		if (!empty($this->params['path'])) {
			if ($customPath = realpath($this->params['path'])) {
				$path = $customPath;
			}
		}
		return $path;
	}

	/**
	 * Restore DB from a SQL dump file.
	 * Lists all the files in the backup folder to select from.
	 *
	 * @return void
	 */
	public function restore() {
		$files = $this->_getFiles();
		$path = $this->_path();
		$this->out('Path: ' . $path);
		$this->out('Files need to start with "dbdump_" and have either .sql or .gz extension.');
		$this->out('Note that dumps created by "create" command will also DROP existing tables!', 2);

		$this->out('Available files:');
		if (empty($files)) {
			return $this->error('No files found...');
		}

		foreach ($files as $key => $file) {
			$size = filesize($path . $file);
			$this->out('[' . $key . '] ' . $file . ' (' . NumberLib::toReadableSize($size) . ')');
		}

		while (true) {
			$x = $this->in('Select File (or q to quit)', null, 'q');
			if ($x === 'q') {
				return $this->error('Aborted!');
			}
			if (!is_numeric($x)) {
				continue;
			}
			$x = (int)$x;
			if (in_array($x, array_keys($files))) {
				break;
			}
		}
		$file = $files[$x];
		$this->out();
		$this->out('Restoring:');
		$this->out($file);
		$looksGood = $this->in(__d('cake_console', 'Look okay?'), array('y', 'n'), 'y');
		if ($looksGood !== 'y') {
			return $this->error('Aborted!');
		}

		$db = ConnectionManager::getDataSource('default');
		$file = BACKUPS . $file;

		$options = array(
			'--user=' . $db->config['login'],
			'--password=' . $db->config['password'],
			'--default-character-set=' . $db->config['encoding'],
			'--host=' . $db->config['host'],
			$db->config['database'],
		);
		if (!empty($this->params['verbose'])) {
			$options[] = '--verbose';
		}
		if ($this->_restore($options, $file)) {
			$this->out('Done :)');
		}
	}

	protected function _restore($options, $file) {
		$command = $this->_command('mysql');

		if (strpos($file, '.gz') !== false || !empty($this->params['compress'])) {
			$command = $this->_command('gunzip') . ' < ' . $file . ' | ' . $command;
		} else {
			$options[] = '< ' . $file;
		}

		if (!empty($options)) {
			$command .= ' ' . implode(' ', $options);
		}
		if (!empty($this->params['dry-run'])) {
			$this->out($command);
			$ret = 0;
		} else {
			exec($command, $output, $ret);
		}
		if (!empty($this->params['verbose']) && !empty($output)) {
			$this->log($output, 'dbdump');
		}
		return $ret === 0;
	}

	/**
	 * Deletes all sql backup files
	 *
	 * @return void
	 */
	public function clear() {
		$files = $this->_getFiles();
		$this->out(count($files) . ' files found');
		$this->out('Aborting');
		return;
		$looksGood = $this->in(__d('cake_console', 'Sure?'), array('y', 'n'), 'y');
		if ($looksGood !== 'y') {
			return $this->error('Aborted!');
		}
		foreach ($files as $file) {
			unlink(BACKUPS . $file);
		}
		$this->out('Done: ' . __('%s deleted', count($files)));
	}

	/**
	 * Automatically removes old dumps and keeps x newest ones
	 *
	 * @return void
	 */
	public function cleanup() {
		//TODO
	}

	/**
	 * Returns available files to restore
	 * in reverse order (newest ones first!)
	 *
	 * @return array Files
	 */
	protected function _getFiles() {
		$Directory = new RecursiveDirectoryIterator(BACKUPS);
		$It = new RecursiveIteratorIterator($Directory);
		$Regex = new RegexIterator($It, '/dbdump_.*?[\.sql|\.gz]$/', RecursiveRegexIterator::GET_MATCH);
		$files = array();
		foreach ($Regex as $v) {
			$files[] = $v[0];
		}
		$files = array_reverse($files);
		return $files;
	}

	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'dry-run' => array(
					'short' => 'd',
					'help' => __d('cake_console', 'Dry run the update, no files will actually be modified.'),
					'boolean' => true
				),
				'tables' => array(
					'short' => 't',
					'help' => __d('cake_console', 'custom tables to dump (separate using , and NO SPACES - use no prefix). Use -t only for prompting tables.'),
				),
				'compress' => array(
					'short' => 'c',
					'help' => __d('cake_console', 'compress using gzip'),
					'boolean' => true
				),
				'path' => array(
					'short' => 'p',
					'help' => __d('cake_console', 'Use a custom backup directory'),
					'default' => ''
				)
			)
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "A Shell to dump and restore SQL databases. The advantage: It uses native cli commands which save a lot of resources and are very fast."))
			->addSubcommand('create', array(
				'help' => __d('cake_console', 'Dump SQL to file'),
				'parser' => $subcommandParser
			))
			->addSubcommand('restore', array(
				'help' => __d('cake_console', 'Restore SQL from file'),
				'parser' => $subcommandParser
			));
	}

}
