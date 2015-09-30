<?php
namespace Setup\Shell;

use Cake\Collection\Collection;
use Cake\Console\Shell;
use Cake\Datasource\ConnectionManager;

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
 * @licence MIT
 */
class DbMaintenanceShell extends Shell {

	/**
	 * Assert proper (UTF8) encoding.
	 *
	 * @return void
	 */
	public function encoding() {
		$db = ConnectionManager::get('default');
		$config = $db->config();
		$database = $config['database'];
		$prefix = ''; //$config['prefix'];
		$encoding = 'utf8';
		$collate = 'utf8_unicode_ci';

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
		$res = $db->query($script);
		if (!$res) {
			return $this->error('Nothing to do...');
		}

		$script = '';
		foreach ($res as $r) {
			$this->out($r['statement'], 1, Shell::VERBOSE);
			$script .= $r['statement'];
		}

		$continue = $this->in(count($res) . ' tables will be altered.', ['Y', 'N'], 'N');
		if (strtoupper($continue) !== 'Y') {
			return $this->error('Aborted!');
		}

		if (!$this->params['dry-run']) {
			$db->query($script);
		} else {
			$this->out($script);
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
		$db = ConnectionManager::get('default');
		$config = $db->config();
		$database = $config['database'];
		$prefix = ''; //$config['prefix'];
		$engines = ['InnoDB', 'MyISAM'];

		while (!$engine) {
			$engine = $this->in('Please select target engine', $engines);
		}
		if (!in_array($engine, $engines)) {
			return $this->error('Please provide a valid target format/engine.');
		}

		$script = <<<SQL
SELECT  CONCAT('ALTER TABLE `', table_name, '` ENGINE=$engine;') AS statement
FROM    information_schema.tables AS tb
WHERE   table_schema = '$database'
AND     table_name LIKE '$prefix%'
AND     `ENGINE` != '$engine'
AND     `TABLE_TYPE` = 'BASE TABLE';
SQL;
		$res = $db->query($script);
		if (!$res) {
			return $this->error('Nothing to do...');
		}

		$script = '';
		foreach ($res as $r) {
			$this->out($r['statement'], 1, Shell::VERBOSE);
			$script .= $r['statement'];
		}

		$continue = $this->in(count($res) . ' tables will be altered.', ['Y', 'N'], 'N');
		if (strtoupper($continue) !== 'Y') {
			return $this->error('Aborted!');
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
		$db = ConnectionManager::get('default');
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

		$res = $db->query($script);
		if (!$res->count()) {
			return $this->error('Nothing to do...');
		}

		$script = '';
		foreach ($res as $r) {
			$script .= $r['statement'];
			$this->out($r['statement'], 1, Shell::VERBOSE);
		}

		$continue = $this->in($res->count() . ' tables will be altered.', ['Y', 'N'], 'N');
		if (strtoupper($continue) !== 'Y') {
			return $this->error('Aborted!');
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
		$db = ConnectionManager::get('test');
		$config = $db->config();
		$database = $config['database'];

		$script = "
SELECT CONCAT('DROP TABLE `', table_name, '`;') AS statement
FROM information_schema.tables AS tb
WHERE   table_schema = '$database'
AND table_name LIKE '$prefix%' OR table_name LIKE '\_%';";

		$res = $db->query($script);
		if (!$res) {
			$this->error('Nothing to do...');
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
				$this->error('Aborted!');
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
	 * Fixes 0000-00-00 00:00:00 dates to NULL.
	 * Also alerts about wrong "DEFAULT NOT NULL" etc.
	 *
	 * @return void
	 */
	public function dates($prefix = null) {
		$db = ConnectionManager::get('default');
		$config = $db->config();
		$database = $config['database'];

		$script = "
SELECT table_name
FROM information_schema.tables AS tb
WHERE   table_schema = '$database'
AND table_name LIKE '$prefix%' OR table_name LIKE '\_%';";

		$res = $db->query($script);
		if (!$res) {
			$this->error('Nothing to do...');
		}
		$tables = new Collection($res);

		$todo = [];

		foreach ($tables as $table) {
			if (substr($table['table_name'], 0, 1) === '_') {
				continue;
			}

			// Structure
			$sql = "DESCRIBE " . $table['table_name'] . ";";
			$this->out('Checking for tables that need updating:', 1, static::VERBOSE);
			$this->out('- ' . $sql, 1, static::VERBOSE);
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
				$conditions[] = "`" . $fieldName . "` = '" . $z . "'";
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
				$todo[] = 'UPDATE ' . $table['table_name'] . ' SET ' . '`' . $fieldName . '` = NULL' . ' WHERE ' . $conditions . ';';
			}
		}

		if (!$todo) {
			$this->out('Nothing to do :)');
			return;
		}
		$this->out(count($todo) . ' tables/fields need updating.');
		$continue = $this->in('Continue?', ['y', 'n'], 'y');
		if ($continue !== 'y') {
			return $this->error('Aborted!');
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

	public function getOptionParser() {
		$subcommandParser = [
			'options' => [
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry run the command, nothing will actually be modified.',
					'boolean' => true
				],
			]
		];

		$tablePrefixParser = $subcommandParser;
		$tablePrefixParser['options']['database'] = [
			'help' => 'Database name, defaults to the currently configured one',
			'default' => ''
		];
		$tablePrefixParser['arguments'] = [
			'action' => ['help' => __('[A]dd or [R]remove.'), 'required' => false],
			'prefix' => ['help' => __('Prefix to work with.'), 'required' => false]
		];

		return parent::getOptionParser()
			->description("A Shell to do some basic database maintenance for you.
Use -d -v (dry-run and verbose mode) to only display queries but not execute them.")
			->addSubcommand('encoding', [
				'help' => 'Convert encoding.',
				'parser' => $subcommandParser
			])
			->addSubcommand('engine', [
				'help' => 'Convert engine.',
				'parser' => $subcommandParser
			])
			->addSubcommand('table_prefix', [
				'help' => 'Add or remove table prefixes.',
				'parser' => $tablePrefixParser
			])
			->addSubcommand('dates', [
				'help' => 'Correct date(time) fields.',
				'parser' => $subcommandParser
			])
			->addSubcommand('cleanup', [
				'help' => 'Cleanup database.',
				'parser' => $subcommandParser
			]);
	}

}
