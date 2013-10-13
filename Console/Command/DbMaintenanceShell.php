<?php
App::uses('AppShell', 'Console/Command');
App::uses('ConnectionManager', 'Model');

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
 * @cakephp 2.x
 * @licence MIT
 */
class DbMaintenanceShell extends AppShell {

	/**
	 * Assert proper (UTF8) encoding.
	 *
	 * @return void
	 */
	public function encoding() {
		$db = ConnectionManager::getDataSource('default');
		$database = $db->config['database'];
		$encoding = 'utf8';
		$collate = 'utf8_unicode_ci';
		$prefix = empty($db->config['prefix']) ? '' : $db->config['prefix'];

		$script = "ALTER DATABASE $database CHARACTER SET $encoding COLLATE $collate;";
		if (!$this->params['dry-run']) {
			$db->execute($script);
		} else {
			$this->out($script);
		}

		$script = <<<SQL
SELECT  CONCAT('ALTER TABLE `', table_name, '` CONVERT TO CHARACTER SET $encoding COLLATE $collate;') AS statement
FROM    information_schema.tables AS tb
WHERE   table_schema = '$database'
AND     table_name LIKE '$prefix%'
AND     `TABLE_TYPE` = 'BASE TABLE';
SQL;
		$res = $db->fetchAll($script);
		if (!$res) {
			$this->error('Nothing to do...');
		}
		foreach ($res as $r) {
			$this->out(' - ' . $r[0]['statement'], 1, Shell::VERBOSE);
		}

		$continue = $this->in(count($res) . ' tables will be altered.', array('Y', 'N'), 'N');
		if (strtoupper($continue) !== 'Y') {
			$this->error('Aborted!');
		}

		$script = '';
		foreach ($res as $r) {
			$script .= $r[0]['statement'];
		}

		if (!$this->params['dry-run']) {
			$db->execute($script);
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
	 * @return void
	 */
	public function engine() {
		$db = ConnectionManager::getDataSource('default');
		$database = $db->config['database'];
		$engines = array('InnoDB', 'MyISAM');
		$prefix = empty($db->config['prefix']) ? '' : $db->config['prefix'];

		if (!empty($this->args[0])) {
			$engine = $this->args[0];
		} else {
			$engine = $this->in('Please select target engine', $engines);
			//$engine = $engines[$engine];
		}
		if (!in_array($engine, $engines)) {
			$this->error('Please provide a valid target format/engine.');
		}

		$script = <<<SQL
SELECT  CONCAT('ALTER TABLE `', table_name, '` ENGINE=$engine;') AS statement
FROM    information_schema.tables AS tb
WHERE   table_schema = '$database'
AND     table_name LIKE '$prefix%'
AND     `ENGINE` != '$engine'
AND     `TABLE_TYPE` = 'BASE TABLE';
SQL;
		$res = $db->fetchAll($script);
		if (!$res) {
			$this->error('Nothing to do...');
		}
		foreach ($res as $r) {
			$this->out(' - ' . $r[0]['statement'], 1, Shell::VERBOSE);
		}

		$continue = $this->in(count($res) . ' tables will be altered.', array('Y', 'N'), 'N');
		if (strtoupper($continue) !== 'Y') {
			$this->error('Aborted!');
		}

		$script = '';
		foreach ($res as $r) {
			$script .= $r[0]['statement'];
		}

		if (!$this->params['dry-run']) {
			$db->execute($script);
		} else {
			$this->out($script);
		}
		$this->out('Done :)');
	}

	/**
	 * Remove tmp or test tables if existent.
	 * - tables starting with underscore [_]
	 * - all tables with the prefix if available, otherwise all tables!
	 *
	 * It is always a good idea to prefix your app with "app_" prefix for example then.
	 *
	 * @return void
	 */
	public function cleanup() {
		$db = ConnectionManager::getDataSource('test');
		$database = $db->config['database'];
		$prefix = empty($db->config['prefix']) ? '' : $db->config['prefix'];

		$script = "
SELECT CONCAT('DROP TABLE `', table_name, '`;') AS statement
FROM information_schema.tables AS tb
WHERE   table_schema = '$database'
AND table_name LIKE '$prefix%' OR table_name LIKE '\_%';";
		$res = $db->fetchAll($script);
		if (!$res) {
			$this->error('Nothing to do...');
		}
		foreach ($res as $r) {
			$this->out(' - ' . $r[0]['statement'], 1, Shell::VERBOSE);
		}

		$this->out('Database ' . $database . ': ' . count($res) . ' tables found');
		if (!$prefix) {
			$res = $this->in('No prefix set! Careful, this will drop all tables in that test datasource, continue?', array('Y', 'N'), 'N');
			if ($res !== 'Y') {
				$this->error('Aborted!');
			}
		}

		$script = '';
		foreach ($res as $r) {
			$script .= $r[0]['statement'];
		}

		if (!$this->params['dry-run']) {
			$db->execute($script);
		} else {
			$this->out($script);
		}
		$this->out('Done :)');
	}

	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'dry-run' => array(
					'short' => 'd',
					'help' => __d('cake_console', 'Dry run the command, nothing will actually be modified.'),
					'boolean' => true
				),
			)
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "A Shell to do some basic maintenance."))
			->addSubcommand('encoding', array(
				'help' => __d('cake_console', 'Convert encoding.'),
				'parser' => $subcommandParser
			))
			->addSubcommand('engine', array(
				'help' => __d('cake_console', 'Convert engine.'),
				'parser' => $subcommandParser
			))
			->addSubcommand('cleanup', array(
				'help' => __d('cake_console', 'Cleanup database.'),
				'parser' => $subcommandParser
			));
	}

}
