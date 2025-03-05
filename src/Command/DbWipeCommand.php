<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Setup\Command\Traits\DbToolsTrait;

/**
 * Hard resets DB by dropping all tables (including phinx migrations tables).
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbWipeCommand extends Command {

	use DbToolsTrait;

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

		$tableTruncates = 'DROP TABLE ' . implode(';' . PHP_EOL . 'DROP TABLE ', $sources) . ';';

		$sql = <<<SQL
SET FOREIGN_KEY_CHECKS = 0;

$tableTruncates

SET FOREIGN_KEY_CHECKS = 1;
SQL;
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
