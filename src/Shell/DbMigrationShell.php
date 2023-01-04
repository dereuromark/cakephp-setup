<?php

namespace Setup\Shell;

use Cake\Collection\Collection;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Setup\Command\Traits\DbToolsTrait;

if (!defined('WINDOWS')) {
	if (substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

/**
 * A Shell to ease database migrations needed
 * - Convert null fields without a default value
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbMigrationShell extends Shell {

	use DbToolsTrait;

	/**
	 * Assert proper non null fields (having a default value, needed for MySQL > 5.6).
	 *
	 * @return void
	 */
	public function nulls() {
		$prefix = '';
		$tables = $this->_getTables($prefix);

		$todo = [];

		$db = $this->_getConnection();

		$this->out('Checking for tables that need updating:', 1, static::VERBOSE);
		foreach ($tables as $table) {
			// Structure
			$sql = 'DESCRIBE ' . $table['table_name'] . ';';
			$this->out('- ' . $sql, 1, static::VERBOSE);
			/** @var \Traversable $res */
			$res = $db->query($sql);
			$fields = (new Collection($res))->toArray();

			$fieldList = [];
			foreach ($fields as $field) {
				$name = $field['Field'];
				if ($name === 'id' || substr($name, -3) === '_id') {
					continue;
				}

				$type = $field['Type'];
				$null = $field['Null'];

				if (preg_match('/^tinyint\([\d]\)/', $type)) {
					if ($null === 'YES' || $field['Default'] !== null) {
						continue;
					}

					$todo[] = 'ALTER TABLE' . ' ' . $table['table_name'] . ' CHANGE `' . $name . '` `' . $name . '` ' . $type . ' NOT NULL DEFAULT \'0\';';

					continue;
				}

				if (!in_array($type, ['longtext', 'mediumtext', 'text']) && !preg_match('/^(varchar|char)\(/', $type)) {
					continue;
				}
				if ($type === 'varchar(36)' || $type === 'char(36)') {
					continue;
				}

				$fieldList[] = $field['Field'];

				if ($null === 'YES' || $field['Default'] !== null) {
					continue;
				}

				// We need to migrate sth
				if (!in_array($type, ['longtext', 'mediumtext', 'text'])) {
					$todo[] = 'ALTER TABLE' . ' ' . $table['table_name'] . ' CHANGE `' . $name . '` `' . $name . '` ' . $type . ' NOT NULL DEFAULT \'\';';
				} else {
					$todo[] = 'ALTER TABLE' . ' ' . $table['table_name'] . ' CHANGE `' . $name . '` `' . $name . '` ' . $type . ' DEFAULT NULL;';
				}
			}
		}

		if (!$todo) {
			$this->out('Nothing to do :)');

			return;
		}

		$this->out(count($todo) . ' tables/fields need updating.');
		if (!$this->param('dry-run')) {
			$continue = $this->in('Continue?', ['y', 'n'], 'y');
			if ($continue !== 'y') {
				$this->abort('Aborted!');
			}
		}
		$sql = implode(PHP_EOL, $todo);
		if (!$this->param('dry-run')) {
			$this->out($sql);

			return;
		}

		$db->query($sql);
		$this->out('Done :)');
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$subcommandParser = [
			'options' => [
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry run the command, nothing will actually be modified. It will output the SQL to copy-and-paste, e.g. into a Migrations file.',
					'boolean' => true,
				],
				'table' => [
					'short' => 't',
					'help' => 'Specific table (separate multiple with comma).',
					'default' => '',
				],
				'connection' => [
					'short' => 'c',
					'help' => 'Use a different connection than `default`.',
					'default' => '',
				],
			],
		];

		return parent::getOptionParser()
			->setDescription("A Shell to do some basic database migration for you.
Use -d -v (dry-run and verbose mode) to only display queries but not execute them.")
			->addSubcommand('nulls', [
				'help' => 'Correct non-nulls of type string/text to have a default value.',
				'parser' => $subcommandParser,
			]);
	}

}
