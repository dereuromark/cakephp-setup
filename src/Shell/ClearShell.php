<?php
namespace Setup\Shell;

use Cake\Cache\Cache;
use Cake\Console\Shell;
use Cake\Filesystem\Folder;

if (!defined('CHMOD_PUBLIC')) {
	define('CHMOD_PUBLIC', 0775);
}

/**
 * @cakephp 2.0
 * @author Mark Scherer
 * @license MIT
 */
class ClearShell extends Shell {

	/**
	 * Shell startup, prints info message about dry run.
	 *
	 * @return void
	 */
	public function startup() {
		parent::startup();
		if (!empty($this->params['dry-run'])) {
			$this->out('<warning>Dry-run mode enabled!</warning>', 1, Shell::QUIET);
		}
	}

	/**
	 * Predefined shorthands
	 */
	public $caches = [
		'm' => 'models', 'p' => 'persistent', 'v' => 'views',
	];

	/**
	 * @deprecated with new command parser help?
	 */
	public function help() {
		$help = <<<TEXT
The Clear Shell deletes all tmp files (cache, logs)
---------------------------------------------------------------
Usage: cake Setup.Clear <command> (cache, logs, all) <args> (p, m, v, css, js, ...)
---------------------------------------------------------------

Commands:

	clear help
		shows this help message.

	clear cache
		delete tmp cache + www cache

	clear logs
		reset all log files

	clear tmp
		delete tmp folder (except cache and logs)

	clear all
		delete tmp + cache + log files

Params:
	-r (remove subfolders as well)
	-v (verbose)
	-d (dry-run)

TEXT;
		$this->out($help);
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

		if (empty($this->args)) {
			$this->engines();
		}
	}

	/**
	 * Clears content of cache engines
	 *
	 * @param mixed any amount of strings - keys of configure cache engines
	 * @return void
	 */
	public function engines() {
		if (!isset($this->_Cleaner)) {
			//App::uses('ClearCacheLib', 'Setup.Lib');
			$this->_Cleaner = new ClearCacheLib();
		}
		$output = call_user_func_array([&$this->_Cleaner, 'engines'], $this->args);

		foreach ($output as $key => $result) {
			$this->out($key . ': ' . ($result ? 'cleared' : 'error'));
		}
	}

	/**
	 * ClearShell::all()
	 *
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
	 * ClearShell::_empty()
	 *
	 * @param string $dir
	 * @param array $excludes
	 * @return void
	 */
	public function _empty($dir, $excludes = []) {
		$Iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir),
			\RecursiveIteratorIterator::CHILD_FIRST);
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
			return $this->error('No dir given', 'Please specify the dir relative to the APP dir or provide an absolute one.');
		}
	}

	/**
	 * Get the option parser
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = [
			'options' => [
				'remove' => [
					'short' => 'r',
					'help' => 'Remove (sub)folders, as well',
					'boolean' => true
				],
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry run the command, no files will actually be deleted. Should be combined with verbose!',
					'boolean' => true
				]
			]
		];

		return parent::getOptionParser()
			->description("The Clear Shell easily deletes all tmp files (cache, logs, ...)")
			->addSubcommand('all', [
				'help' => 'Clear all',
				'parser' => $subcommandParser
			])
			->addSubcommand('tmp', [
				'help' => 'Clear tmp (except cache)',
				'parser' => $subcommandParser
			])
			->addSubcommand('cache', [
				'help' => 'Clear cache',
				'parser' => $subcommandParser
			])
			->addSubcommand('engines', [
				'help' => 'Clear cache engines',
				'parser' => $subcommandParser
			])
			->addSubcommand('logs', [
				'help' => 'Clear log files',
				'parser' => $subcommandParser
			])
			->addSubcommand('custom', [
				'help' => 'Clear custom dir',
				'parser' => $subcommandParser
			]);
	}

}
