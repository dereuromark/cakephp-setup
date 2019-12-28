<?php

namespace Setup\Shell;

use Cake\Collection\Collection;
use Cake\Console\Shell;
use Exception;
use Setup\Shell\Traits\DbToolsTrait;

if (!defined('WINDOWS')) {
	if (substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

/**
 * A Shell to maintain the database
 * - Convert table format
 * - Assert encoding
 * - Cleanup utility
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbMaintenanceShell extends Shell {

	use DbToolsTrait;

	/**
	 * Assert proper (UTF8) encoding.
	 *
	 * @return void
	 */
	public function encoding() {
		$db = $this->_getConnection();
		$config = $db->config();
		$database = $config['database'];
		$prefix = ''; //$config['prefix'];
		$encoding = 'utf8mb4';
		$collate = 'utf8mb4_unicode_ci';

		try {
			$script = "ALTER DATABASE $database CHARACTER SET $encoding COLLATE $collate;";
			if (!$this->params['dry-run']) {
				$db->query($script);
			} else {
				$this->out($script);
			}
		} catch (Exception $e) {
			$this->err('Could not alter database: ' . $e->getMessage() . ' - Skipping.');
		}

		$script = <<<SQL
SELECT  CONCAT('ALTER TABLE `', table_name, '` CONVERT TO CHARACTER SET $encoding COLLATE $collate;') AS statement
FROM    information_schema.tables AS tb
WHERE   table_schema = '$database'
AND     table_name LIKE '$prefix%'
AND     `TABLE_TYPE` = 'BASE TABLE';
SQL;
		/** @var \Cake\Database\Statement\StatementDecorator $res */
		$res = $db->query($script);
		if (!$res->count()) {
			$this->abort('Nothing to do...');
		}

		$script = [];
		foreach ($res as $r) {
			$script[] = $r['statement'];
		}

		if (!$this->param('dry-run')) {
			$continue = $this->in(count($res) . ' tables will be altered.', ['Y', 'N'], 'N');
			if (strtoupper($continue) !== 'Y') {
				$this->abort('Aborted!');
			}
		}

		foreach ($script as $row) {
			preg_match('/`(.+)`/', $row, $matches);
			$table = $matches[1];
			$sql = "ALTER TABLE `$table` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

			if ($this->params['interactive']) {
				$in = $this->in('Fix table ' . $table . '?', ['y', 'n'], 'y');
				if ($in !== 'y') {
					$this->out(' - skipping ' . $table);
					continue;
				}
			}

			if (!$this->params['dry-run']) {
				$this->out(' - fixing table ' . $table, 1, Shell::VERBOSE);
				$db->query($row);
				$db->query($sql);
			} else {
				$this->out($row);
				$this->out($sql);
			}
		}
		$this->out('Done :)');
	}

	/**
	 * Convert database table engine.
	 *
	 * Args (optional)
	 * - target engine (MyIsam, InnoDB)
	 *
	 * @param string|null $engine Engine to convert to.
	 * @return void
	 */
	public function engine($engine = null) {
		$db = $this->_getConnection();
		$config = $db->config();
		$database = $config['database'];
		$prefix = ''; //$config['prefix'];
		$engines = ['InnoDB', 'MyISAM'];

		while (!$engine) {
			$engine = $this->in('Please select target engine', $engines);
		}
		if (!in_array($engine, $engines)) {
			$this->abort('Please provide a valid target format/engine.');
		}

		$script = <<<SQL
SELECT  CONCAT('ALTER TABLE `', table_name, '` ENGINE=$engine;') AS statement
FROM    information_schema.tables AS tb
WHERE   table_schema = '$database'
AND     table_name LIKE '$prefix%'
AND     `ENGINE` != '$engine'
AND     `TABLE_TYPE` = 'BASE TABLE';
SQL;
		/** @var \Cake\Database\Statement\StatementDecorator $res */
		$res = $db->query($script);
		if (!$res->count()) {
			$this->abort('Nothing to do...');
		}

		$script = '';
		foreach ($res as $r) {
			$this->out($r['statement'], 1, Shell::VERBOSE);
			$script .= $r['statement'];
		}

		if (!$this->param('dry-run')) {
			$continue = $this->in(count($res) . ' tables will be altered.', ['Y', 'N'], 'N');
			if (strtoupper($continue) !== 'Y') {
				$this->abort('Aborted!');
			}
		}

		if (!$this->params['dry-run']) {
			$db->query($script);
		} else {
			$this->out($script);
		}
		$this->out('Done :)');
	}

	/**
	 * Adds or removes table prefixes.
	 *
	 * Since CakePHP 3.0 does not support them, this is very useful when
	 * migrating 2.x apps that use those prefixes.
	 *
	 * @param string|null $action
	 * @param string|null $prefix
	 * @return void
	 */
	public function tablePrefix($action = null, $prefix = null) {
		$db = $this->_getConnection();
		$config = $db->config();
		$database = $config['database'];
		if (!empty($this->params['database'])) {
			$database = $this->params['database'];
		}

		while (!$action || !in_array($action, ['A', 'R'], true)) {
			$action = $this->in('Add or remove?', ['A', 'R']);
		}

		while (!$prefix) {
			$prefix = $this->in('Please select prefix:');
		}

		$space = "\n";
		if ($action === 'R') {
			$length = mb_strlen($prefix) + 1;
			$script = <<<SQL
SELECT  CONCAT('RENAME TABLE `$database`.`', table_name, '` TO `$database`.`', SUBSTR(table_name, $length), '`;$space') AS statement
FROM    information_schema.tables AS tb
WHERE   table_schema = '$database'
AND     table_name LIKE '$prefix%'
AND     `TABLE_TYPE` = 'BASE TABLE';
SQL;
		} else {
			$script = <<<SQL
SELECT  CONCAT('RENAME TABLE `$database`.`', table_name, '` TO `$database`.`$prefix', table_name, '`;$space') AS statement
FROM    information_schema.tables AS tb
WHERE   table_schema = '$database'
AND     table_name NOT LIKE '$prefix%'
AND     `TABLE_TYPE` = 'BASE TABLE';
SQL;
		}

		/** @var \Cake\Database\Statement\StatementDecorator $res */
		$res = $db->query($script);
		if (!$res->count()) {
			$this->abort('Nothing to do...');
		}

		$script = '';
		foreach ($res as $r) {
			$script .= $r['statement'];
			$this->out($r['statement'], 1, Shell::VERBOSE);
		}

		if (!$this->param('dry-run')) {
			$continue = $this->in($res->count() . ' tables will be altered.', ['Y', 'N'], 'N');
			if (strtoupper($continue) !== 'Y') {
				$this->abort('Aborted!');
			}
		}

		if (!$this->params['dry-run']) {
			$db->query($script);
		} else {
			$this->out($script);
		}
		$this->out('Done :)');
	}

	/**
	 * Remove grouped, tmp or test tables if existent.
	 * - tables starting with underscore [_]
	 * - all tables with the prefix if available, otherwise all tables!
	 *
	 * It is always a good idea to prefix your non-cake-app stuff with "foo_" prefix for example then.
	 *
	 * @param string|null $prefix
	 * @return void
	 */
	public function cleanup($prefix = null) {
		$db = $this->_getConnection();
		$config = $db->config();
		$database = $config['database'];

		$script = "
SELECT CONCAT('DROP TABLE `', table_name, '`;') AS statement
FROM information_schema.tables AS tb
WHERE   table_schema = '$database'
AND table_name LIKE '$prefix%' OR table_name LIKE '\_%';";

		/** @var \Cake\Database\Statement\StatementDecorator $res */
		$res = $db->query($script);
		if (!$res->count()) {
			$this->abort('Nothing to do...');
		}

		$script = '';
		foreach ($res as $r) {
			$script .= $r['statement'];
			$this->out($r['statement'], 1, Shell::VERBOSE);
		}

		$this->out('Database ' . $database . ': ' . count($res) . ' tables found');
		if (!$prefix) {
			$in = $this->in('No prefix set! Careful, this will drop all tables in that database, continue?', ['Y', 'N'], 'N');
			if ($in !== 'Y') {
				$this->abort('Aborted!');
			}
		}

		if (!$this->params['dry-run']) {
			$db->query($script);
		} else {
			$this->out($script);
		}
		$this->out('Done :)');
	}

	/**
	 * Fixes 0 foreign keys to NULL.
	 * Also alerts about wrong "DEFAULT NOT NULL" etc.
	 *
	 * @param string|null $prefix
	 * @return void
	 */
	public function foreignKeys($prefix = null) {
		$tables = $this->_getTables($prefix);

		$todo = [];

		$db = $this->_getConnection();
		foreach ($tables as $table) {
			// Structure
			$sql = 'DESCRIBE ' . $table['table_name'] . ';';
			$this->out('- ' . $sql, 1, static::VERBOSE);

			/** @var \Cake\Database\Statement\StatementDecorator $res */
			$res = $db->query($sql);
			$fields = new Collection($res);

			$fieldList = [];
			foreach ($fields as $field) {
				$name = $field['Field'];
				$type = $field['Type'];
				$null = $field['Null'];
				if (!preg_match('/\w+\_id$/', $name) || !preg_match('/^int\(1[10]\)|char\(36\)|varchar\(36\)/', $type)) {
					continue;
				}

				$fieldList[] = $field['Field'];

				$isUuid = $type === 'char(36)' || $type === 'varchar(36)';
				if ($null === 'YES' && $field['Default'] === null) {
					continue;
				}

				if (!empty($field['Key']) && $field['Key'] === 'PRI') {
					continue;
				}
				if (!empty($field['Extra']) && $field['Extra'] === 'auto_increment') {
					continue;
				}

				// We need to migrate sth
				$todo[] = 'ALTER TABLE' . ' ' . $table['table_name'] . ' CHANGE `' . $name . '` `' . $name . '` ' . $type . ' NULL DEFAULT NULL;';
			}

			if (empty($fieldList)) {
				continue;
			}

			// Data
			$z = '0';
			$conditions = [];
			foreach ($fieldList as $fieldName) {
				$conditions[] = '`' . $fieldName . "` = '" . $z . "'";
			}
			$conditions = implode(' OR ', $conditions);

			$sql = 'SELECT COUNT(*) as count FROM ' . $table['table_name'] . ' WHERE ' . $conditions;
			$this->out('Checking for records that need updating:', 1, static::VERBOSE);
			$this->out(' - ' . $sql, 1, static::VERBOSE);
			/** @var \Cake\Database\Statement\StatementDecorator $res */
			$res = $db->query($sql);
			$res = (new Collection($res))->toArray();
			if (empty($res[0]['count'])) {
				continue;
			}

			$sets = [];
			foreach ($fieldList as $fieldName) {
				$todo[] = 'UPDATE ' . $table['table_name'] . ' SET ' . '`' . $fieldName . '` = NULL' . ' WHERE `' . $fieldName . '` = \'' . $z . '\';';
			}
		}

		if (!$todo) {
			$this->out('Nothing to do :)');
			return;
		}

		$this->out(count($todo) . ' tables/fields need updating.');

		if (!$this->param('dry-run')) {
			$continue = $this->in('Continue?', ['Y', 'N'], 'Y');
			if ($continue !== 'Y') {
				$this->abort('Aborted!');
			}
		}

		$sql = implode(PHP_EOL, $todo);
		if (!empty($this->params['dry-run'])) {
			$this->out($sql);
			return;
		}

		// Execute
		$db->query($sql);
		$this->out('Done :)');
	}

	/**
	 * Fixes 0000-00-00 00:00:00 dates to NULL.
	 * Also alerts about wrong "DEFAULT NOT NULL" etc.
	 *
	 * @param string|null $prefix
	 * @return void
	 */
	public function dates($prefix = null) {
		$tables = $this->_getTables($prefix);

		$todo = [];

		$db = $this->_getConnection();

		$this->out('Checking for tables that need updating:', 1, static::VERBOSE);
		foreach ($tables as $table) {
			// Structure
			$sql = 'DESCRIBE ' . $table['table_name'] . ';';
			$this->out('- ' . $sql, 1, static::VERBOSE);
			/** @var \Cake\Database\Statement\StatementDecorator $res */
			$res = $db->query($sql);
			$fields = new Collection($res);

			$fieldList = [];
			foreach ($fields as $field) {
				$name = $field['Field'];
				$type = $field['Type'];
				$null = $field['Null'];
				if ($type !== 'date' && $type !== 'datetime') {
					continue;
				}
				$fieldList[] = $field['Field'];

				if ($null === 'YES' && empty($field['Default'])) {
					continue;
				}
				// We need to migrate sth
				$todo[] = 'ALTER TABLE' . ' ' . $table['table_name'] . ' CHANGE `' . $name . '` `' . $name . '` ' . $type . ' NULL DEFAULT NULL;';
			}

			if (empty($fieldList)) {
				continue;
			}

			// Data for $fieldList
			$z = '0000-00-00 00:00:00';
			$conditions = [];
			foreach ($fieldList as $fieldName) {
				$conditions[] = '`' . $fieldName . "` = '" . $z . "'";
			}
			$conditions = implode(' OR ', $conditions);

			$sql = 'SELECT COUNT(*) as count FROM ' . $table['table_name'] . ' WHERE ' . $conditions;
			$this->out('Checking for records that need updating:', 1, static::VERBOSE);
			$this->out(' - ' . $sql, 1, static::VERBOSE);
			$res = $db->query($sql);
			$res = (new Collection($res))->toArray();
			if (empty($res[0]['count'])) {
				continue;
			}

			$sets = [];
			foreach ($fieldList as $fieldName) {
				$todo[] = 'UPDATE ' . $table['table_name'] . ' SET ' . '`' . $fieldName . '` = NULL' . ' WHERE `' . $fieldName . '` = \'' . $z . '\';';
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
		if (!empty($this->params['dry-run'])) {
			$this->out($sql);
			return;
		}

		// Execute
		$db->query($sql);
		$this->out('Done :)');
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): \Cake\Console\ConsoleOptionParser {
		$subcommandParser = [
			'options' => [
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry run the command, nothing will actually be modified. It will output the SQL to copy-and-paste, e.g. into a Migrations file.',
					'boolean' => true,
				],
				'connection' => [
					'short' => 'c',
					'help' => 'Use a different connection than `default`.',
					'default' => '',
				],
				'table' => [
					'short' => 't',
					'help' => 'Specific table (separate multiple with comma).',
					'default' => '',
				],
				'interactive' => [
					'short' => 'i',
					'help' => 'Interactive mode.',
					'boolean' => true,
				],
			],
		];

		$tablePrefixParser = $subcommandParser;
		$tablePrefixParser['options']['database'] = [
			'help' => 'Database name, defaults to the currently configured one',
			'default' => '',
		];
		$tablePrefixParser['arguments'] = [
			'action' => ['help' => '[A]dd or [R]remove.', 'required' => false],
			'prefix' => ['help' => 'Prefix to work with.', 'required' => false],
		];

		return parent::getOptionParser()
			->setDescription("A Shell to do some basic database maintenance for you.
Use -d -v (dry-run and verbose mode) to only display queries but not execute them.")
			->addSubcommand('encoding', [
				'help' => 'Convert encoding to `utf8mb4`.',
				'parser' => $subcommandParser,
			])
			->addSubcommand('engine', [
				'help' => 'Convert engine (MyIsam, InnoDB).',
				'parser' => $subcommandParser,
			])
			->addSubcommand('table_prefix', [
				'help' => 'Add or remove table prefixes.',
				'parser' => $tablePrefixParser,
			])
			->addSubcommand('dates', [
				'help' => 'Correct date(time) fields and alerts of wrong field types.',
				'parser' => $subcommandParser,
			])
			->addSubcommand('foreign_keys', [
				'help' => 'Correct foreign key fields and alerts of wrong field types.',
				'parser' => $subcommandParser,
			])
			->addSubcommand('cleanup', [
				'help' => 'Cleanup database.',
				'parser' => $subcommandParser,
			]);
	}

}
