<?php

namespace Setup\Shell;

use Cake\Cache\Cache;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Error\Debugger;
use Cake\Mailer\Email;
use Cake\Mailer\Mailer;
use Cake\Utility\Security;
use Exception;

/**
 * Outputs the current configuration
 * - DB (default and test)
 * - Cache
 * - ...
 *
 * @author Mark Scherer
 * @license MIT
 */
class CurrentConfigShell extends Shell {

	/**
	 * @return void
	 */
	public function phpinfo() {
		ob_start();
		phpinfo();
		$phpinfo = ob_get_contents();
		ob_end_clean();

		$this->out($phpinfo);
	}

	/**
	 * @return void
	 */
	public function display() {
		$this->info('Security Salt: ' . Security::getSalt());
		$this->info('Full Base URL: ' . Configure::read('App.fullBaseUrl'));

		$this->out('');

		$this->info('Email Config:');
		$config = (array)Mailer::getConfig('default');
		foreach ($config as $key => $value) {
			$this->out(' - ' . $key . ': ' . $value);
		}

		$this->out('');

		$this->info('ENV:');
		foreach ($_ENV as $key => $value) {
			$this->out(' - ' . $key . ': ' . $value);
		}
	}

	/**
	 * @param string|null $key
	 * @return void
	 */
	public function configure($key = null) {
		$config = Configure::read($key);
		if (is_array($config)) {
			ksort($config);
		}
		$type = Debugger::getType($config);
		if (is_array($config)) {
			$type .= ' and size of ' . count($config);
		}
		$this->out(print_r($config, true));
		$this->info('of type ' . $type);
	}

	/**
	 * @return void
	 */
	public function validate() {
		$this->out('DB default:');
		try {
			$db = ConnectionManager::get('default');
			$this->out(print_r($db->config(), true));
		} catch (Exception $e) {
			$this->err($e->getMessage());
		}

		$this->out('');
		$this->out('DB test:');
		try {
			$db = ConnectionManager::get('test');
			$this->out(print_r($db->config(), true));
		} catch (Exception $e) {
			$this->err($e->getMessage());
		}

		$this->out('');
		$this->out('Cache:');
		$this->out(print_r(Cache::getConfig('_cake_core_'), true));
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		return parent::getOptionParser()
			->setDescription('A Shell to display current system and application configs.')
			->addSubcommand('display', [
				'help' => 'Displays runtime configuration (config and environment).',
			])
			->addSubcommand('configure', [
				'help' => 'Outputs configure values for given dot path.',
			])
			->addSubcommand('validate', [
				'help' => 'Checks application config for CLI (DB, Cache).',
			])
			->addSubcommand('phpinfo', [
				'help' => 'Display phpinfo() for CLI.
Use `bin/cake current_config phpinfo | grep xdebug` for example to get all xdebug relevant info from it.
Use the /admin/setup-extra/configuration/phpinfo backend to see phpinfo() for non-CLI (can differ!).',
			]);
	}

}
