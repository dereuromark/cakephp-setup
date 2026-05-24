<?php

namespace Setup\TestSuite;

use Cake\Datasource\ConnectionManager;

/**
 * @mixin \Cake\TestSuite\TestCase
 */
trait DriverSkipTrait {

	/**
	 * @param string $type
	 * @param string $message
	 * @return void
	 */
	protected function skipIfNotDriver(string $type, string $message = '') {
		$config = ConnectionManager::getConfig('test');
		$this->skipIf(!str_contains((string) $config['driver'], $type), $message);
	}

	/**
	 * @param string $type
	 * @param string $message
	 * @return void
	 */
	protected function skipIfDriver(string $type, string $message = '') {
		$config = ConnectionManager::getConfig('test');
		$this->skipIf(str_contains((string) $config['driver'], $type), $message);
	}

}
