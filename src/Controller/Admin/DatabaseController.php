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
	protected $modelClass = '';

	/**
	 * @param string|null $table
	 * @return \Cake\Http\Response|null|void
	 */
	public function foreignKeys($table = null) {
		$Model = TableRegistry::getTableLocator()->get('Sessions');
		/** @var \Cake\Database\Connection $db */
		$db = $Model->query();

		if (!$table) {
			$dbTables = $db->execute('SHOW TABLE STATUS')->fetchAll();
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

			$Model = TableRegistry::getTableLocator()->get($dbTable['Name']);

			$schema = $Model->getSchema();
			$tables[$dbTable['Name']] = [
				'schema' => $schema,
				//'relations' => $Model->get
			];
		}

		$this->set(compact('tables'));
	}

}
