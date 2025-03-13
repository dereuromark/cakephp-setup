<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Text;
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
		$name = substr($config['driver'], strrpos($config['driver'], '\\') + 1);
		$config['scheme'] = strtolower($name);
		if ($config['scheme'] === 'sqlite' && $config['database'] === ':memory:') {
			$this->io->warning('Using in-memory database, skipping.');

			return;
		}

		$dsn = Text::insert('{scheme}://{username}:{password}@{host}', $config, ['before' => '{', 'after' => '}']);
		ConnectionManager::setConfig('tmp', ['url' => $dsn]);

		/** @var \Cake\Database\Connection $connection */
		$connection = ConnectionManager::get('tmp');
		$connection->execute('CREATE DATABASE IF NOT EXISTS ' . $config['database'] . ' ' .
			'DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;');

		$this->io->success('Done: ' . $config['database']);
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
		];

		return parent::getOptionParser()
			->setDescription(static::getDescription())
			->addOptions($options);
	}

}
