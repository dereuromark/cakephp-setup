<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\Driver\Mysql;
use Setup\Command\Traits\DbToolsTrait;

/**
 * Resets DB by truncating all tables (except for migration tracking tables).
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbResetCommand extends Command {

	use DbToolsTrait;

	protected Arguments $args;

	protected ConsoleIo $io;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Resets DB by truncating all tables (except for migration tracking tables).';
	}

	/**
	 * Truncates application tables while preserving migration tracking.
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
		foreach ($sources as $key => $source) {
			if ($source === 'cake_migrations' || $source === 'phinxlog' || str_contains((string)$source, '_phinxlog')) {
				unset($sources[$key]);
			}
		}

		if (!$sources) {
			$io->out('No application tables to truncate.');

			return static::CODE_SUCCESS;
		}

		if (!$args->getOption('dry-run') && !$db->getDriver() instanceof Mysql) {
			$io->abort('db reset currently supports execution on MySQL only. Use --dry-run to inspect the proposed SQL.');
		}

		$sources = array_map(fn ($source) => $db->getDriver()->quoteIdentifier($source), $sources);
		$tableTruncates = 'TRUNCATE TABLE ' . implode(';' . PHP_EOL . 'TRUNCATE TABLE ', $sources) . ';';

		$sql = <<<SQL
SET FOREIGN_KEY_CHECKS = 0;

$tableTruncates

SET FOREIGN_KEY_CHECKS = 1;
SQL;
		$this->io->out('--------', 1, ConsoleIo::VERBOSE);
		$this->io->out($sql, 1, ConsoleIo::VERBOSE);
		$this->io->out('--------', 1, ConsoleIo::VERBOSE);

		$this->io->out('Truncating ' . count($sources) . ' tables');
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
				'help' => 'Dry run the reset, no data will be removed.',
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
