<?php
namespace Setup\Shell;

use Cake\Console\Shell;
use Cake\Filesystem\Folder;
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
				$db->execute($script);
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
		foreach ($res as $r) {
			$this->out(' - ' . $r['statement'], 1, Shell::VERBOSE);
		}

		$continue = $this->in(count($res) . ' tables will be altered.', array('Y', 'N'), 'N');
		if (strtoupper($continue) !== 'Y') {
			return $this->error('Aborted!');
		}

		$script = '';
		foreach ($res as $r) {
			$script .= $r['statement'];
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
	public function engine($engine = null) {
		$db = ConnectionManager::get('default');
		$config = $db->config();
		$database = $config['database'];
		$prefix = ''; //$config['prefix'];
		$engines = array('InnoDB', 'MyISAM');

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
		foreach ($res as $r) {
			$this->out(' - ' . $r['statement'], 1, Shell::VERBOSE);
		}

		$continue = $this->in(count($res) . ' tables will be altered.', array('Y', 'N'), 'N');
		if (strtoupper($continue) !== 'Y') {
			return $this->error('Aborted!');
		}

		$script = '';
		foreach ($res as $r) {
			$script .= $r['statement'];
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
		$db = ConnectionManager::get('test');
		$config = $db->config();
		$database = $config['database'];
		$prefix = ''; //$config['prefix'];

		$script = "
SELECT CONCAT('DROP TABLE `', table_name, '`;') AS statement
FROM information_schema.tables AS tb
WHERE   table_schema = '$database'
AND table_name LIKE '$prefix%' OR table_name LIKE '\_%';";

		$res = $db->query($script);
		if (!$res) {
			$this->error('Nothing to do...');
		}
		foreach ($res as $r) {
			$this->out(' - ' . $r['statement'], 1, Shell::VERBOSE);
		}

		$this->out('Database ' . $database . ': ' . count($res) . ' tables found');
		if (!$prefix) {
			$in = $this->in('No prefix set! Careful, this will drop all tables in that test datasource, continue?', array('Y', 'N'), 'N');
			if ($in !== 'Y') {
				$this->error('Aborted!');
			}
		}

		$script = '';
		foreach ($res as $r) {
			$script .= $r['statement'];
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
					'help' => 'Dry run the command, nothing will actually be modified.',
					'boolean' => true
				),
			)
		);

		return parent::getOptionParser()
			->description("A Shell to do some basic maintenance.")
			->addSubcommand('encoding', array(
				'help' => 'Convert encoding.',
				'parser' => $subcommandParser
			))
			->addSubcommand('engine', array(
				'help' => 'Convert engine.',
				'parser' => $subcommandParser
			))
			->addSubcommand('cleanup', array(
				'help' => 'Cleanup database.',
				'parser' => $subcommandParser
			));
	}

}
