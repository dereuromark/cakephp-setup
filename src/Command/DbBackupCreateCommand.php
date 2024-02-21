<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Setup\Command\Traits\DbBackupTrait;
use Setup\Command\Traits\DbToolsTrait;

/**
 * Dumps DB backup.
 *
 * - Custom backup path possible
 * - Custom tables possible (prompted or manually passed)
 * - Clear and cleanup
 * - Could be used in a cronjob environment (e.g.: backup every 12 hours)
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbBackupCreateCommand extends Command {

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

		/** @var \Cake\Database\Schema\Collection $schemaCollection */
		$schemaCollection = $db->getSchemaCollection();
		$sources = $schemaCollection->listTables();

		if ($args->getOption('reset')) {
			$this->clearAll();
		}

		/** @var string|null $tables */
		$tables = $args->getOption('tables');
		if ($tables) {
			$sources = explode(',', $tables);
			foreach ($sources as $key => $val) {
				$sources[$key] = $usePrefix . $val;
			}
			$optionStrings[] = '--tables ' . implode(' ', $sources);
			$file .= '_custom';
		} elseif ($usePrefix) {
			foreach ($sources as $key => $source) {
				if (!str_starts_with($source, $usePrefix)) {
					unset($sources[$key]);
				}
			}
			$optionStrings[] = '--tables ' . implode(' ', $sources);
			$file .= '_' . rtrim($usePrefix, '_');
		}
		$file .= '.sql';
		if ($args->getOption('compress')) {
			$optionStrings[] = '| gzip';
			$file .= '.gz';
		}
		$optionStrings[] = '> ' . $file;

		$this->io->out('Backup will be written to:');
		$this->io->out(' - ' . $this->_path());
		if (!$this->args->getOption('force')) {
			$looksGood = $this->io->askChoice('Look okay?', ['y', 'n'], 'y');
			if ($looksGood !== 'y') {
				$this->io->abort('Aborted!');
			}
		}

		if ($this->create($optionStrings)) {
			$this->io->out('Done :)');
		}
	}

	/**
	 * @param array<string> $optionStrings
	 *
	 * @return bool
	 */
	protected function create(array $optionStrings): bool {
		$command = $this->_command('mysqldump');
		if ($optionStrings) {
			$command .= ' ' . implode(' ', $optionStrings);
		}
		if ($this->args->getOption('dry-run')) {
			$this->io->out($command);
			$ret = static::CODE_SUCCESS;
		} else {
			exec($command, $output, $ret);
		}

		return $ret === static::CODE_SUCCESS;
	}

	/**
	 * Deletes all sql backup files
	 *
	 * @return void
	 */
	protected function clearAll(): void {
		$files = $this->_getFiles(BACKUPS);
		$this->io->out(count($files) . ' existing files found');

		$dryRun = $this->args->getOption('dry-run');
		foreach ($files as $file) {
			if (!$dryRun) {
				unlink(BACKUPS . $file);
			}
		}
		$this->io->out(($dryRun ? 'Dry-Run' : 'Done') . ': ' . sprintf('%s deleted', count($files)));
		$this->io->out();
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
			'reset' => [
				'short' => 'r',
				'help' => 'Remove all existing backup files in the process.',
				'boolean' => true,
			],
		];

		return parent::getOptionParser()
			->setDescription('dump and restore SQL databases. The advantage: It uses native CLI commands which save a lot of resources and are very fast.')
			->addArgument('file', [
				'help' => 'Use a specific backup file (needs to be an absolute path).',
				'optional' => true,
			])
			->addOptions($options);
	}

}
