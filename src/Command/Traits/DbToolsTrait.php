<?php

namespace Setup\Command\Traits;

use Cake\Collection\Collection;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Text;

/**
 * @mixin \Cake\Command\Command
 */
trait DbToolsTrait {

	/**
	 * @param string $name
	 *
	 * @return \Cake\Database\Connection
	 */
	protected function _getConnection(string $name = 'default') {
		if (!empty($this->params['connection'])) {
			$name = $this->params['connection'];
		}

		/** @var \Cake\Database\Connection $connection */
		$connection = ConnectionManager::get($name);

		return $connection;
	}

	/**
	 * @param string $prefix
	 * @return array
	 */
	protected function _getTables(string $prefix): array {
		$db = $this->_getConnection();
		$config = $db->config();
		$database = $config['database'];

		$script = "
SELECT table_name
FROM information_schema.tables AS tb
WHERE   table_schema = '$database'
AND table_name LIKE '$prefix%' OR table_name LIKE '\_%';";

		/** @var \Cake\Database\Statement\StatementDecorator $res */
		$res = $db->query($script);
		if (!$res->count()) {
			$this->abort('Nothing to do...');
		}
		$tables = new Collection($res);

		$whitelist = Text::tokenize((string)$this->param('table'));

		$tables = $tables->toArray();
		foreach ($tables as $key => $table) {
			if (substr($table['table_name'], 0, 1) === '_') {
				unset($tables[$key]);
			}

			if ($whitelist && !in_array($table['table_name'], $whitelist)) {
				unset($tables[$key]);
			}
		}

		return $tables;
	}

}
