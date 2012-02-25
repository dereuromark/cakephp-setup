<?php
App::uses('AppShell', 'Console/Command');
App::uses('ConnectionManager', 'Model');

/**
 * Outputs the current configuration
 * - DB (default and test)
 * - Cache
 * - ...
 * 
 * @author Mark Scherer
 * @cakephp 2
 * @license MIT
 * 2011-11-06 ms
 */
class CurrentConfigShell extends AppShell {

	public function main() {
		$this->out('DB default:');
		$db = ConnectionManager::getDataSource('default');
		$this->out(print_r($db->config, true));
		
		$this->out('');
		$this->out('DB test:');
		$db = ConnectionManager::getDataSource('test');
		$this->out(print_r($db->config, true));
		
		$this->out('');
		$this->out('Cache:');
		$this->out(print_r(Cache::config('_cake_core_'), true));
		

	}




}

