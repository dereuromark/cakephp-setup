<?php

namespace Setup\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Number;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

if (!defined('BACKUPS')) {
	define('BACKUPS', ROOT . DS . 'files' . DS . 'backup' . DS);
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
 * A Shell to easily create and restore SQL backup files.
 *
 * - Tested on windows and linux
 * - Uses settings from database.php
 * - Custom backup path possible
 * - Custom tables possible (prompted or manually passed)
 * - Clear and cleanup
 * - Could be used in a cronjob environment (e.g.: backup every 12 hours)
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbBackupShell extends Shell {

	/**
	 * @return void
	 */
	public function startup(): void {
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
		$db = ConnectionManager::get('default');
		$config = $db->config();
		$usePrefix = empty($config['prefix']) ? '' : $config['prefix'];

		$file = $this->_path() . 'backup_' . date('Y-m-d--H-i-s');

		$optionStrings = [
			'--user="' . ($config['username'] ?? '') . '"',
			'--password="' . ($config['password'] ?? '') . '"',
			'--default-character-set=' . ($config['encoding'] ?? 'utf8'),
			'--host=' . ($config['host'] ?? 'localhost'),
			'--databases ' . $config['database'],
			'--no-create-db',
		];

		$schemaCollection = $db->getSchemaCollection();
		$sources = $schemaCollection->listTables();

		if (array_key_exists('tables', $this->params) && empty($this->params['tables'])) {
			// prompt for tables
			foreach ($sources as $key => $source) {
				$this->out('[' . $key . '] ' . $source);
			}
			$tables = $this->in('What tables (separated by comma without spaces)', null, null);
			$tables = explode(',', $tables);
			$tableList = [];
			foreach ($tables as $table) {
				if (isset($sources[(int)$table])) {
					$tableList[] = $sources[(int)$table];
				}
			}
			$optionStrings[] = '--tables ' . implode(' ', $tableList);
			$file .= '_custom';

		} elseif (!empty($this->params['tables'])) {
			$sources = explode(',', $this->params['tables']);
			foreach ($sources as $key => $val) {
				$sources[$key] = $usePrefix . $val;
			}
			$optionStrings[] = '--tables ' . implode(' ', $sources);
			$file .= '_custom';
		} elseif ($usePrefix) {
			foreach ($sources as $key => $source) {
				if (strpos($source, $usePrefix) !== 0) {
					unset($sources[$key]);
				}
			}
			$optionStrings[] = '--tables ' . implode(' ', $sources);
			$file .= '_' . rtrim($usePrefix, '_');
		}
		$file .= '.sql';
		if (!empty($this->params['compress'])) {
			$optionStrings[] = '| gzip';
			$file .= '.gz';
		}
		$optionStrings[] = '> ' . $file;

		$this->out('Backup will be written to:');
		$this->out(' - ' . $this->_path());
		$looksGood = $this->in('Look okay?', ['y', 'n'], 'y');
		if ($looksGood !== 'y') {
			$this->abort('Aborted!');
		}

		if ($this->_create($optionStrings)) {
			$this->out('Done :)');
		}
	}

	/**
	 * @param array<string> $optionStrings
	 *
	 * @return bool
	 */
	protected function _create(array $optionStrings) {
		$command = $this->_command('mysqldump');
		if ($optionStrings) {
			$command .= ' ' . implode(' ', $optionStrings);
		}
		if (!empty($this->params['dry-run'])) {
			$this->out($command);
			$ret = 0;
		} else {
			exec($command, $output, $ret);
		}

		return $ret === 0;
	}

	/**
	 * @param string $command
	 *
	 * @return string
	 */
	protected function _command($command) {
		switch ($command) {
			case 'gzip':
			case 'gunzip':
				$path = Configure::read('Cli.gitPath');

				break;
			default:
				$path = Configure::read('Cli.mysqlPath');
		}

		/** @var bool $windows */
		$windows = WINDOWS;

		return ($windows && $path ? $path : '') . $command;
	}

	/**
	 * @return string
	 */
	protected function _path() {
		$path = BACKUPS;
		if (!empty($this->params['path'])) {
			$customPath = realpath($this->params['path']);
			if ($customPath) {
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
		$file = $this->_getFile();

		$this->out('');
		$this->out('Restoring: ' . pathinfo($file, PATHINFO_BASENAME));

		if (!$this->params['force']) {
			$looksGood = $this->in('Look okay?', ['y', 'n'], 'y');
			if ($looksGood !== 'y') {
				$this->abort('Aborted!');
			}
		}

		$db = ConnectionManager::get('default');
		$config = $db->config();

		$optionStrings = [
			'--user="' . $config['username'] . '"',
			'--password="' . $config['password'] . '"',
			'--default-character-set=' . ($config['encoding'] ?? 'utf-8'),
			'--host=' . $config['host'],
			'' . $config['database'],
		];

		if (!empty($this->params['verbose'])) {
			$optionStrings[] = '--verbose';
		}
		if ($this->_restore($optionStrings, $file)) {
			$this->out('Done :)');
		}
	}

	/**
	 * @return string
	 */
	protected function _getFile() {
		if ($this->args && $this->args[0]) {
			$file = realpath($this->args[0]);
			if (!$file || !file_exists($file)) {
				$this->abort(sprintf('Invalid file `%s`', $this->args[0]));
			}

			return $file;
		}

		$files = $this->_getFiles();
		$path = $this->_path();
		$this->out('Path: ' . $path);
		$this->out('Files need to start with "backup_" and have either .sql or .gz extension.');
		//$this->out('Note that dumps created by "create" command will also DROP existing tables!', 2);

		$this->out('Available files:');
		if (empty($files)) {
			$this->abort('No files found...');
		}

		foreach ($files as $key => $file) {
			$size = (int)filesize($path . $file);
			$this->out('[' . $key . '] ' . $file . ' (' . Number::toReadableSize($size) . ')');
		}

		while (true) {
			$x = $this->in('Select File (or q to quit)', null, 'q');
			if ($x === 'q') {
				$this->abort('Aborted!');
			}
			if (!is_numeric($x)) {
				continue;
			}
			$x = (int)$x;
			if (array_key_exists($x, $files)) {
				break;
			}
		}
		$file = $files[$x];

		$file = BACKUPS . $file;

		return $file;
	}

	/**
	 * @param array<string> $optionStrings
	 * @param string $file
	 *
	 * @return bool
	 */
	protected function _restore(array $optionStrings, string $file) {
		$command = $this->_command('mysql');

		if (strpos($file, '.gz') !== false || !empty($this->params['compress'])) {
			$command = $this->_command('gunzip') . ' < ' . $file . ' | ' . $command;
		} else {
			$optionStrings[] = '< ' . $file;
		}

		if (!empty($optionStrings)) {
			$command .= ' ' . implode(' ', $optionStrings);
		}
		if (!empty($this->params['dry-run'])) {
			$this->out($command);
			$ret = 0;
		} else {
			exec($command, $output, $ret);
		}
		if (!empty($this->params['verbose']) && !empty($output)) {
			$this->log($output, 'info');
		}

		return $ret === 0;
	}

	/**
	 * Deletes all sql backup files
	 *
	 * @return void
	 */
	public function clearAll() {
		$files = $this->_getFiles();
		$this->out(count($files) . ' files found');
		$this->out('Aborting');

		$looksGood = $this->in('Sure?', ['y', 'n'], 'y');
		if ($looksGood !== 'y') {
			$this->abort('Aborted!');
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
		$Regex = new RegexIterator($It, '/\bbackup_.*?[\.sql|\.gz]$/', RecursiveRegexIterator::GET_MATCH);
		$files = [];
		foreach ($Regex as $v) {
			$files[] = $v[0];
		}
		$files = array_reverse($files);

		return $files;
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$subcommandParser = [
			'options' => [
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry run the update, no files will actually be modified.',
					'boolean' => true,
				],
				'tables' => [
					'short' => 't',
					'help' => 'custom tables to dump (separate using , and NO SPACES - use no prefix). Use -t only for prompting tables.',
				],
				'compress' => [
					'short' => 'c',
					'help' => 'compress using gzip',
					'boolean' => true,
				],
				'path' => [
					'short' => 'p',
					'help' => 'Use a custom backup directory',
					'default' => '',
				],
				'force' => [
					'short' => 'f',
					'help' => 'Force the command, do not ask for confirmation.',
					'boolean' => true,
				],
			],
			'arguments' => [
				'file' => [
					'help' => 'Use a specific backup file (needs to be an absolute path).',
					'optional' => true,
				],
			],
		];

		return parent::getOptionParser()
			->setDescription('A Shell to dump and restore SQL databases. The advantage: It uses native CLI commands which save a lot of resources and are very fast.')
			->addSubcommand('create', [
				'help' => 'Dump SQL to file',
				'parser' => $subcommandParser,
			])
			->addSubcommand('clearAll', [
				'help' => 'Clear all SQL files.',
				'parser' => $subcommandParser,
			])
			->addSubcommand('restore', [
				'help' => 'Restore SQL from file',
				'parser' => $subcommandParser,
			]);
	}

}
