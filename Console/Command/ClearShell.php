<?php

if (!defined('CHMOD_PUBLIC')) {
	define('CHMOD_PUBLIC', 0770);
}
App::uses('AppShell', 'Console/Command');
App::uses('Folder', 'Utility');

/**
 * @cakephp 2.0
 * @author Mark Scherer
 * @license MIT
 */
class ClearShell extends AppShell {

	/**
	 * Shell startup, prints info message about dry run.
	 *
	 * @return void
	 */
	public function startup() {
		parent::startup();
		if (!empty($this->params['dry-run'])) {
			$this->out(__d('cake_console', '<warning>Dry-run mode enabled!</warning>'), 1, Shell::QUIET);
		}
	}

	public function main() {
		$this->help();
	}

	/**
	 * Predefined shorthands
	 */
	public $caches = array(
		'm' => 'models', 'p' => 'persistent', 'v' => 'views',
	);

	/**
	 * Predefined matches in webroot
	 */
	public $webrootCaches = array(
		'css' => 'css/ccss', 'js' => 'js/cjs'
	);

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
				$this->args = array('css', 'js');
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
			if (in_array($arg, array('css', 'js'))) {
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
			App::uses('ClearCacheLib', 'Setup.Lib');
			$this->_Cleaner = new ClearCacheLib();
		}
		$output = call_user_func_array(array(&$this->_Cleaner, 'engines'), $this->args);

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
		$this->_empty(TMP, array(TMP . 'logs' . DS, TMP . 'cache' . DS));
	}

	/**
	 * ClearShell::_empty()
	 *
	 * @param string $dir
	 * @param array $excludes
	 * @return void
	 */
	public function _empty($dir, $excludes = array()) {
		$Iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
			RecursiveIteratorIterator::CHILD_FIRST);
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
	 * ClearShell::webroot()
	 *
	 * @return void
	 */
	public function webroot() {
		$this->css();
		$this->js();
	}

	/**
	 * Delete the given dir (must be relative to APP)
	 *
	 * @return void
	 */
	public function custom() {
		if (empty($this->args)) {
			return $this->error('No dir given', 'Please specify the dir relative to the APP dir');
		}
	}

	/**
	 * Expects /app/webroot/js/cjs/
	 *
	 * @return void
	 */
	public function js() {
		$folder = WWW_ROOT . 'js' . DS . 'cjs' . DS;
		$Handle = new Folder($folder);

		$res = $Handle->read(false, true, true);
		$count = 0;

		foreach ($res[1] as $r) {
			unlink($r);
			$count++;
		}
		//return __('cjs cleared %s', $count);
		$this->out(__('clear webroot: js (%s)', $count));
	}

	/**
	 * Expects /app/webroot/css/ccss/
	 *
	 * @return void
	 */
	public function css() {
		$folder = WWW_ROOT . 'css' . DS . 'ccss' . DS;
		$Handle = new Folder($folder);
		$res = $Handle->read(false, true, true);
		$count = 0;

		foreach ($res[1] as $r) {
			unlink($r);
			$count++;
		}
		//return __('ccss cleared %s', $count);
		$this->out(__('clear webroot: css (%s)', $count));
	}

	/**
	 * Get the option parser
	 *
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'remove' => array(
					'short' => 'r',
					'help' => __d('cake_console', 'Remove subfolders, as well'),
					'boolean' => true
				),
				'dry-run' => array(
					'short' => 'd',
					'help' => __d('cake_console', 'Dry run the clear command, no files will actually be deleted. Should be combined with verbose!'),
					'boolean' => true
				)
			)
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "The Clear Shell deletes all tmp files (cache, logs)"))
			->addSubcommand('all', array(
				'help' => __d('cake_console', 'Clear all'),
				'parser' => $subcommandParser
			))
			->addSubcommand('tmp', array(
				'help' => __d('cake_console', 'Clear tmp (except logs and cache)'),
				'parser' => $subcommandParser
			))
			->addSubcommand('cache', array(
				'help' => __d('cake_console', 'Clear cache'),
				'parser' => $subcommandParser
			))
			->addSubcommand('engines', array(
				'help' => __d('cake_console', 'Clear cache engines'),
				'parser' => $subcommandParser
			))
			->addSubcommand('logs', array(
				'help' => __d('cake_console', 'Clear log files'),
				'parser' => $subcommandParser
			))
			->addSubcommand('webroot', array(
				'help' => __d('cake_console', 'Clear js/css files'),
				'parser' => $subcommandParser
			))
			->addSubcommand('js', array(
				'help' => __d('cake_console', 'Clear js files'),
				'parser' => $subcommandParser
			))
			->addSubcommand('css', array(
				'help' => __d('cake_console', 'Clear css files'),
				'parser' => $subcommandParser
			))
				->addSubcommand('custom', array(
				'help' => __d('cake_console', 'Clear custom dir'),
				'parser' => $subcommandParser
			));
	}

}
