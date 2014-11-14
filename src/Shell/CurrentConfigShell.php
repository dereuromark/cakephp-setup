<?php
namespace Setup\Shell;

use Cake\Console\Shell;
use App\Model\ConnectionManager;
use Cake\Cache\Cache;
/**
 * Outputs the current configuration
 * - DB (default and test)
 * - Cache
 * - ...
 *
 * @author Mark Scherer
 * @cakephp 2
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
			$db = ConnectionManager::getDataSource('default');
			$this->out(print_r($db->config, true));
		} catch (Exception $e) {
			$this->err($e->getMessage());
		}

		$this->out('');
		$this->out('DB test:');
		try {
			$db = ConnectionManager::getDataSource('test');
			$this->out(print_r($db->config, true));
		} catch (Exception $e) {
			$this->err($e->getMessage());
		}

		$this->out('');
		$this->out('Cache:');
		$this->out(print_r(Cache::config('_cake_core_'), true));
	}

}
