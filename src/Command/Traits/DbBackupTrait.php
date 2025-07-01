<?php

namespace Setup\Command\Traits;

use Cake\Core\Configure;
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
 * @mixin \Cake\Command\Command
 */
trait DbBackupTrait {

	/**
	 * @param string $command
	 *
	 * @return string
	 */
	protected function _command(string $command): string {
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
	protected function _path(): string {
		$path = BACKUPS;

		return $path;
	}

	/**
	 * Returns available files to restore
	 * in reverse order (newest ones first!)
	 *
	 * @param string $path
	 *
	 * @return array Files
	 */
	protected function _getFiles(string $path): array {
		$Directory = new RecursiveDirectoryIterator($path);
		$It = new RecursiveIteratorIterator($Directory);
		$Regex = new RegexIterator($It, '/\bbackup_.*?[\.sql|\.gz]$/', RecursiveRegexIterator::GET_MATCH);
		$files = [];
		foreach ($Regex as $v) {
			$files[] = $v[0];
		}
		$files = array_reverse($files);

		return $files;
	}

}
