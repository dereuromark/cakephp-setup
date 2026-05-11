<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Setup\Command\Traits\DbToolsTrait;

/**
 * Hard resets DB by dropping all tables (including phinx migrations tables).
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbWipeCommand extends Command {

	use DbToolsTrait;

	protected Arguments $args;

	protected ConsoleIo $io;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Resets DB by dropping all tables (incl phinx migrations tables).';
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

		$db = $this->_getConnection((string)$args->getOption('connection'));

		/** @var \Cake\Database\Schema\Collection $schemaCollection */
		$schemaCollection = $db->getSchemaCollection();
		$sources = $schemaCollection->listTables();

		// FK-check disabling is driver-specific. The previous command emitted
		// `SET FOREIGN_KEY_CHECKS` unconditionally, which is MySQL syntax and
		// blows up on Postgres / SQLite / SQL Server. Map each driver to its
		// equivalent off/on toggle.
		$driver = $db->getDriver();
		[$fkOff, $fkOn] = match (true) {
			$driver instanceof Mysql => ['SET FOREIGN_KEY_CHECKS = 0;', 'SET FOREIGN_KEY_CHECKS = 1;'],
			$driver instanceof Postgres => ["SET session_replication_role = 'replica';", "SET session_replication_role = 'origin';"],
			$driver instanceof Sqlite => ['PRAGMA foreign_keys = OFF;', 'PRAGMA foreign_keys = ON;'],
			$driver instanceof Sqlserver => ['EXEC sp_MSforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT ALL";', 'EXEC sp_MSforeachtable "ALTER TABLE ? CHECK CONSTRAINT ALL";'],
			default => ['', ''],
		};

		// On SQLite, prefer DROP TABLE IF EXISTS so a transient inconsistency
		// (e.g. another connection already dropped a table) doesn't abort the
		// whole wipe.
		$dropPrefix = $driver instanceof Sqlite ? 'DROP TABLE IF EXISTS ' : 'DROP TABLE ';
		$tableTruncates = $dropPrefix . implode(';' . PHP_EOL . $dropPrefix, $sources) . ';';

		$sql = trim(implode("\n\n", array_filter([$fkOff, $tableTruncates, $fkOn]))) . "\n";
		$this->io->out('--------', 1, ConsoleIo::VERBOSE);
		$this->io->out($sql, 1, ConsoleIo::VERBOSE);
		$this->io->out('--------', 1, ConsoleIo::VERBOSE);

		$this->io->out('Dropping ' . count($sources) . ' tables');
		if (!$this->args->getOption('dry-run') && !$this->args->getOption('force')) {
			$looksGood = $this->io->askChoice('Sure?', ['y', 'n'], 'y');
			if ($looksGood !== 'y') {
				$this->io->abort('Aborted!');
			}
		}

		if (!$this->args->getOption('dry-run')) {
			$db->execute($sql);
		}

		$this->io->out('Done ' . ($this->args->getOption('dry-run') ? 'DRY-RUN' : '') . ' :)');
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$options = [
			'dry-run' => [
				'short' => 'd',
				'help' => 'Dry run the reset, no tables will be removed.',
				'boolean' => true,
			],
			'force' => [
				'short' => 'f',
				'help' => 'Force the command, do not ask for confirmation.',
				'boolean' => true,
			],
			'connection' => [
				'short' => 'c',
				'help' => 'The datasource connection to use.',
				'default' => 'default',
			],
		];

		return parent::getOptionParser()
			->setDescription(static::getDescription() . ' Note: It disables foreign key checks to do this.')
			->addOptions($options);
	}

}
