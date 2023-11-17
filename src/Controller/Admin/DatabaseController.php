<?php

namespace Setup\Controller\Admin;

use App\Controller\AppController;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

class DatabaseController extends AppController {

	/**
	 * @var string|null
	 */
	protected ?string $modelClass = '';

	/**
	 * @param string|null $table
	 * @return \Cake\Http\Response|null|void
	 */
	public function foreignKeys($table = null) {
		$Model = TableRegistry::getTableLocator()->get('Sessions');
		/** @var \Cake\Database\Connection $db */
		$db = $Model->getConnection();

		if (!$table) {
			$dbTables = $db->execute('SHOW TABLE STATUS')->fetchAll(PDO::FETCH_ASSOC);
			$dbTables = (new Collection($dbTables))->toArray();
		} else {
			$dbTables = [
				[
					'Name' => $table,
				],
			];
		}

		$tables = [];
		foreach ($dbTables as $dbTable) {
			if (preg_match('/phinxlog$/', $dbTable['Name'])) {
				continue;
			}
			$blacklist = Configure::read('Setup.blacklistedTables');
			if ($blacklist && in_array($dbTable['Name'], $blacklist, true)) {
				continue;
			}

			$name = $dbTable['Name'];
			$Model = TableRegistry::getTableLocator()->get($name, ['allowFallbackClass' => true]);

			$schema = $Model->getSchema();
			$tables[$dbTable['Name']] = [
				'schema' => $schema,
			];
		}

		$this->set(compact('tables'));
	}

}
