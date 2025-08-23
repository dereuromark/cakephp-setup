<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\Connection;
use Setup\Command\Traits\DbToolsTrait;

/**
 * Inits DB(s).
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbInitCommand extends Command {

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
		return 'Inits DB(s) if not yet existing. Uses default connection if not specified otherwise.';
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$this->args = $args;
		$this->io = $io;

		$connection = $this->_getConnection((string)$args->getOption('connection'));
		$config = $connection->config();
		$dbName = $config['database'];

		// For SQLite
		$name = substr($config['driver'], strrpos($config['driver'], '\\') + 1);
		$config['scheme'] = strtolower($name);
		if ($config['scheme'] === 'sqlite' && $config['database'] === ':memory:') {
			$this->io->warning('Using in-memory database, skipping.');

			return;
		}

		// Create a new config without database for checking/creating
		$tempConfig = $config;
		$tempConfig['database'] = ''; // Set to empty string instead of unsetting

		$tempConnection = new Connection($tempConfig);

		$quotedDbName = $tempConnection->getDriver()->quote($dbName);
		$result = $tempConnection->execute('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ' . $quotedDbName)->fetch();
		if ($result) {
			$this->io->info('Database already exists: ' . $dbName);

			return;
		}

		$sql = 'CREATE DATABASE IF NOT EXISTS ' . $tempConnection->getDriver()->quoteIdentifier($dbName)
			. ' ' . 'DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;';
		if ($args->getOption('dry-run')) {
			$this->io->info($sql);
		} else {
			$tempConnection->execute($sql);
		}

		$this->io->success('Done: ' . $dbName);
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$options = [
			'connection' => [
				'short' => 'c',
				'help' => 'The datasource connection to use.',
				'default' => 'default',
			],
			'dry-run' => [
				'short' => 'd',
				'help' => 'Dry-Run it.',
				'boolean' => true,
			],
		];

		return parent::getOptionParser()
			->setDescription(static::getDescription())
			->addOptions($options);
	}

}
