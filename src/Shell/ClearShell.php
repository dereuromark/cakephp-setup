<?php

namespace Setup\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

if (!defined('CHMOD_PUBLIC')) {
	define('CHMOD_PUBLIC', 0775);
}

/**
 * @author Mark Scherer
 * @license MIT
 * @deprecated Use core cache shell
 */
class ClearShell extends Shell {

	/**
	 * Shell startup, prints info message about dry run.
	 *
	 * @return void
	 */
	public function startup(): void {
		parent::startup();
		if (!empty($this->params['dry-run'])) {
			$this->out('<warning>Dry-run mode enabled!</warning>', 1, Shell::QUIET);
		}
	}

	/**
	 * Predefined shorthands
	 *
	 * @var array
	 */
	public $caches = [
		'm' => 'models', 'p' => 'persistent', 'v' => 'views',
	];

	/**
	 * Clean out empty folders that only contain an "empty" placeholder file.
	 *
	 * @param string|null $path
	 *
	 * @return void
	 */
	public function emptyFolders($path = null) {
		if ($path) {
			$path = realpath($path);
		}
		if (!$path) {
			$path = ROOT . DS;
		}

		$this->out('Clearing empty folders in ' . $path);

		$this->_clearEmpty($path);
	}

	/**
	 * @param string $dir
	 * @return void
	 */
	public function _clearEmpty($dir) {
		$Iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir),
			RecursiveIteratorIterator::CHILD_FIRST,
		);
		/** @var \SplFileInfo $path */
		foreach ($Iterator as $path) {
			$fullPath = $path->__toString();

			if (substr($path->getFilename(), 0, 1) === '.' || strpos($fullPath, DS . '.')) {
				continue;
			}
			if (strpos($fullPath, DS . 'vendor' . DS) || strpos($fullPath, DS . 'tmp' . DS) || strpos($fullPath, DS . 'logs' . DS)) {
				continue;
			}

			if ($path->isDir()) {
				$this->_clearEmpty($fullPath);

				continue;
			}

			if ($path->getFilename() !== 'empty' || trim(file_get_contents($fullPath)) !== '') {
				continue;
			}

			$path = str_replace(ROOT . DS, '/', $fullPath);
			$this->out('- ' . $path);
			if (empty($this->params['dry-run'])) {
				unlink($fullPath);
			}
		}
	}

	/**
	 * Delete logs and subfolders (traces, detailed logs, ...)
	 * pass a specific folder to delete only this subfolder (defaults to all)
	 *
	 * @return void
	 */
	public function logs() {
		$this->out('Deleting logs:');
		if (!empty($this->args)) {
			foreach ($this->args as $arg) {
				if (!is_dir(LOGS . $arg)) {
					$this->err('No log dir \'' . $arg . '\'');

					continue;
				}
				$this->out('\'' . $arg . '\' emptied');
				$this->_empty(LOGS . $arg);
			}
		} else {
			$this->_empty(LOGS);
			$this->out('All log files deleted');
		}
	}

	/**
	 * No args: all (files + cache engines)
	 * single dirs:
	 * - js, css, m, p, v,
	 * groups:
	 * - webroot to clear js/css cache
	 * - app to clear application cache files
	 *
	 * @return void
	 */
	public function cache() {
		if (count($this->args) === 1) {
			if ($this->args[0] === 'webroot') {
				$this->args = ['css', 'js'];
			} elseif ($this->args[0] === 'app') {
				$this->args = array_values($this->caches);
			}
		}
		foreach ($this->args as $key => $val) {
			if (array_key_exists($val, $this->caches)) {
				$this->args[$key] = $this->caches[$val];
			}
		}

		$this->out('Deleting cache files:');

		if (empty($this->args)) {
			$this->_empty(CACHE);
			$this->out('Complete cache dir emptied');

			return;
		}
		foreach ($this->args as $arg) {
			if (in_array($arg, ['css', 'js'])) {
				$this->{$arg}();

				continue;
			}
			if (!is_dir(CACHE . $arg)) {
				$this->err('No cache dir \'' . $arg . '\'');

				continue;
			}
			$this->_empty(CACHE . $arg);
			$this->out('Cache \'' . $arg . '\' deleted');
		}
	}

	/**
	 * @return void
	 */
	public function all() {
		$this->cache();
		$this->logs();
		$this->tmp();
	}

	/**
	 * ClearShell::tmp()
	 *
	 * @return void
	 */
	public function tmp() {
		$this->_empty(TMP, [TMP . 'cache' . DS]);
	}

	/**
	 * @param string $dir
	 * @param array $excludes
	 * @return void
	 */
	public function _empty($dir, $excludes = []) {
		$Iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir),
			RecursiveIteratorIterator::CHILD_FIRST,
		);
		foreach ($Iterator as $path) {
			$fullPath = $path->__toString();
			$continue = false;
			foreach ($excludes as $exclude) {
				if (strpos($fullPath, $exclude) === 0) {
					$continue = true;

					break;
				}
			}
			if ($continue) {
				continue;
			}
			if ($path->isDir() && empty($this->params['remove'])) {
				continue;
			}

			if ($path->isDir()) {
				if (!empty($this->params['verbose'])) {
					$this->out('Removing dir: ' . $fullPath);
				}
				if (empty($this->params['dry-run'])) {
					rmdir($fullPath);
				}
			} else {
				if (!empty($this->params['verbose'])) {
					$this->out('Removing file: ' . $fullPath);
				}
				if (empty($this->params['dry-run'])) {
					unlink($fullPath);
				}
			}
		}
	}

	/**
	 * Delete the given dir (must be relative to APP)
	 *
	 * @return void
	 */
	public function custom() {
		if (empty($this->args)) {
			$this->abort('No dir given. Please specify the dir relative to the APP dir or provide an absolute one.');
		}
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$subcommandParser = [
			'options' => [
				'remove' => [
					'short' => 'r',
					'help' => 'Remove (sub)folders, as well',
					'boolean' => true,
				],
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry run the command, no files will actually be deleted. Should be combined with verbose!',
					'boolean' => true,
				],
			],
		];

		return parent::getOptionParser()
			->setDescription('The Clear Shell easily deletes all tmp files (cache, logs, ...)')
			->addSubcommand('all', [
				'help' => 'Clear all',
				'parser' => $subcommandParser,
			])
			->addSubcommand('tmp', [
				'help' => 'Clear tmp (except cache)',
				'parser' => $subcommandParser,
			])
			->addSubcommand('cache', [
				'help' => 'Clear cache',
				'parser' => $subcommandParser,
			])
			->addSubcommand('logs', [
				'help' => 'Clear log files',
				'parser' => $subcommandParser,
			])
			->addSubcommand('custom', [
				'help' => 'Clear custom dir',
				'parser' => $subcommandParser,
			])->addSubcommand('empty_folders', [
				'help' => 'Clear empty folders',
				'parser' => $subcommandParser,
			]);
	}

}
