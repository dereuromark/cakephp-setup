<?php
namespace Setup\Shell;

use Cake\Console\Shell;
use Cake\Datasource\ConnectionManager;
use Cake\Cache\Cache;

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
	 * CurrentConfigShell::main()
	 *
	 * @return void
	 */
	public function main() {
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

}
