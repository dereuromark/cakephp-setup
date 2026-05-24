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
	if (str_starts_with(PHP_OS, 'WIN')) {
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
		$path = match ($command) {
			'gzip', 'gunzip' => Configure::read('Cli.gitPath'),
			default => Configure::read('Cli.mysqlPath'),
		};

		/** @var bool $windows */
		$windows = WINDOWS;

		return ($windows && $path ? $path : '') . $command;
	}

	/**
	 * @return string
	 */
	protected function _path(): string {
		return BACKUPS;
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

		return array_reverse($files);
	}

}
