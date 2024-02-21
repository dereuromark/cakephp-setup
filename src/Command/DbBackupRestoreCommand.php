<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Number;
use Setup\Command\Traits\DbBackupTrait;
use Setup\Command\Traits\DbToolsTrait;

/**
 * Restores DB backup.
 *
 * - Custom backup path possible
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbBackupRestoreCommand extends Command {

	use DbToolsTrait;
	use DbBackupTrait;

	/**
	 * @var \Cake\Console\Arguments
	 */
	protected $args;

	/**
	 * @var \Cake\Console\ConsoleIo
	 */
	protected $io;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Restores SQL database from a backup file.';
	}

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		if (!is_dir(BACKUPS)) {
			mkdir(BACKUPS, CHMOD_PUBLIC, true);
		}
	}

	/**
	 * Creates a new user including a freshly hashed password.
	 *
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$this->args = $args;
		$this->io = $io;

		$db = $this->_getConnection();
		$config = $db->config();
		$file = $this->_getFile();

		$this->io->out('');
		$this->io->out('Restoring: ' . pathinfo($file, PATHINFO_BASENAME));

		if (!$this->args->getOption('force')) {
			$looksGood = $this->io->askChoice('Look okay?', ['y', 'n'], 'y');
			if ($looksGood !== 'y') {
				$this->io->abort('Aborted!');
			}
		}

		$optionStrings = [
			'--user="' . $config['username'] . '"',
			'--password="' . $config['password'] . '"',
			'--default-character-set=' . ($config['encoding'] ?? 'utf-8'),
			'--host=' . $config['host'],
			'' . $config['database'],
		];

		if ($this->args->getOption('verbose')) {
			$optionStrings[] = '--verbose';
		}
		if ($this->restore($optionStrings, $file)) {
			$this->io->out('Done :)');
		}
	}

	/**
	 * @return string
	 */
	protected function _getFile(): string {
		$path = $this->args->getArgument('path');
		if ($path) {
			$file = realpath($path);
			if (!$file || !file_exists($file)) {
				$this->io->abort(sprintf('Invalid file (path) `%s`', $path));
			}

			return $file;
		}

		$files = $this->_getFiles(BACKUPS);
		$path = $this->_path();
		$this->io->out('Path: ' . $path);
		$this->io->out('Files need to start with "backup_" and have either .sql or .gz extension.');

		$this->io->out('Available files:');
		if (!$files) {
			$this->io->abort('No files found...');
		}

		foreach ($files as $key => $file) {
			$size = (int)filesize($path . $file);
			$this->io->out('[' . $key . '] ' . $file . ' (' . Number::toReadableSize($size) . ')');
		}

		$options = array_combine(array_keys($files), array_keys($files)) + ['q' => 'q'];
		while (true) {
			$x = $this->io->askChoice('Select File (or q to quit)', $options, 'q');
			if ($x === 'q') {
				$this->io->abort('Aborted!');
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
	protected function restore(array $optionStrings, string $file): bool {
		$command = $this->_command('mysql');

		if (str_contains($file, '.gz') || $this->args->getOption('compress')) {
			$command = $this->_command('gunzip') . ' < ' . $file . ' | ' . $command;
		} else {
			$optionStrings[] = '< ' . $file;
		}

		if (!empty($optionStrings)) {
			$command .= ' ' . implode(' ', $optionStrings);
		}
		if ($this->args->getOption('dry-run')) {
			$this->io->out($command);
			$ret = static::CODE_SUCCESS;
		} else {
			exec($command, $output, $ret);
		}
		if ($this->args->getOption('verbose') && !empty($output)) {
			$this->log(implode(PHP_EOL, $output), 'info');
		}

		return $ret === static::CODE_SUCCESS;
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$options = [
			'dry-run' => [
				'short' => 'd',
				'help' => 'Dry run the update, no files will actually be modified.',
				'boolean' => true,
			],
			'compress' => [
				'short' => 'c',
				'help' => 'Is compressed using gzip',
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
		];

		return parent::getOptionParser()
			->setDescription(static::getDescription())
			->addArgument('file', [
				'help' => 'Use a specific backup file (needs to be an absolute path).',
				'optional' => true,
			])
			->addOptions($options);
	}

}
