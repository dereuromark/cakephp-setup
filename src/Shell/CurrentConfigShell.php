<?php
namespace Setup\Shell;

use Cake\Cache\Cache;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Mailer\Email;
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

		$this->out($phpinfo);
	}

	/**
	 * @return void
	 */
	public function display() {
		$this->info('Security Salt: ' . Security::getSalt());

		$this->info('Email Config:');
		$config = Email::getConfig('default');
		foreach ($config as $key => $value) {
			$this->out(' - ' . $key . ': ' . $value);
		}

		$this->info('ENV:');
		foreach ($_ENV as $key => $value) {
			$this->out(' - ' . $key . ': ' . $value);
		}

		$this->out();
		$this->info('Config:');
		$config = Configure::read();
		ksort($config);
		$this->out(print_r($config, true));
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
		$this->out(print_r(Cache::config('_cake_core_'), true));
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser() {
		return parent::getOptionParser()
			->description('A Shell to display current system and application configs.')
			->addSubcommand('display', [
				'help' => 'Displays runtime configuration (config and environment).',
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
