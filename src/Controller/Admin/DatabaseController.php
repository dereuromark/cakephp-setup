<?php
namespace Setup\Controller\Admin;

use App\Controller\AppController;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

class DatabaseController extends AppController {

	/**
	 * @var string|bool
	 */
	public $modelClass = false;

	/**
	 * @param string|null $table
	 * @return \Cake\Http\Response|null
	 */
	public function foreignKeys($table = null) {
		$Model = TableRegistry::get('Model');
		/** @var \Cake\Database\Connection $db */
		$db = $Model->getConnection();

		if (!$table) {
			$dbTables = $db->query('SHOW TABLE STATUS');
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

			$Model = TableRegistry::get($dbTable['Name']);

			$schema = $Model->getSchema();
			$tables[$dbTable['Name']] = [
				'schema' => $schema,
				//'relations' => $Model->get
			];
		}

		$this->set(compact('tables'));
	}

}
