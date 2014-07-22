<?php
App::uses('AppShell', 'Console/Command');
App::uses('ConnectionManager', 'Model');

/**
 * Cleanup Shell
 *
 * @author Mark Scherer
 * @license MIT
 */
class CleanupShell extends AppShell {

	/**
	 * Shell startup, prints info message about dry run.
	 *
	 * @return void
	 */
	public function startup() {
		parent::startup();
		if ($this->params['dry-run']) {
			$this->out(__d('cake_console', '<warning>Dry-run mode enabled!</warning>'), 1, Shell::QUIET);
		}
	}

	/**
	 * Display help.
	 *
	 * @return void
	 */
	public function main() {
		$this->help();
	}

	//@deprecated with new command parser help?

	public function help() {
		$help = <<<TEXT
The Cleanup Shell takes care of some cleanup stuff to be done once in a while
---------------------------------------------------------------
Usage: cake Setup.Cleanup <command>
---------------------------------------------------------------

Commands:

	help
		shows this help message.

	tables
		delete tmp and test tables

Params:
	-v (verbose)
	-d (dry-run)

TEXT;
		$this->out($help);
	}

	/**
	 * Remove all test/tmp tables
	 *
	 * @return void
	 */
	public function tables() {
		$db = ConnectionManager::getDataSource('default');
		//$res = $db->query('show tables');
		$sources = $db->listSources();

		$toDelete = array();
		foreach ($sources as $source) {
			if (preg_match('/^test_/', $source)) {
				$toDelete[] = $source;
				$this->out('- ' . $source);
			}
		}

		$in = $this->in('Continue?', array('y', 'n'), 'n');
		if ($in !== 'y') {
			return $this->error('Aborted!');
		}

		foreach ($toDelete as $tableName) {
			$db->query('DROP TABLE IF EXISTS `' . $tableName . '`');
		}

		$this->out(count($toDelete) . ' removed');

		/*
		if (!empty($this->args)) {
			$this->_removeTables($this->args);
		} else {
			$this->_removeTables();
		}
		*/
		$this->out('Done');
	}

	/**
	 * Get the option parser
	 *
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'dry-run' => array(
					'short' => 'd',
					'help' => __d('cake_console', 'Dry run the clear command, no tables will actually be dropped. Should be combined with verbose!'),
					'boolean' => true
				)
			)
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "The Clear Shell deletes all tmp files (cache, logs)"))
			->addSubcommand('tables', array(
				'help' => __d('cake_console', 'Clear tmp/test tables'),
				'parser' => $subcommandParser
			));
	}

}
