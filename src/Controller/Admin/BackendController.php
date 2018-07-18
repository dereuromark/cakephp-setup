<?php
namespace Setup\Controller\Admin;

use App\Controller\AppController;
use Cake\Cache\Cache;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

class BackendController extends AppController {

	/**
	 * @var string|bool
	 */
	public $modelClass = false;

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function phpinfo() {
		$this->viewBuilder()->layout('ajax');
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function session() {
		$timestamp = $this->request->session()->read('Config.time');

		$time = new Time($timestamp);

		$sessionConfig = Configure::read('Session');
		$sessionId = $this->request->session()->id();
		if ($sessionConfig['defaults'] === 'database') {
			$sessionData = TableRegistry::get('Sessions')->get($sessionId);
		} else {
			$sessionData = [
				'id' => $sessionId,
			];
		}

		$this->set(compact('sessionData'));

		$this->set(compact('time', 'sessionConfig'));
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function cache() {
		if ($this->request->is(['post', 'put'])) {
			$cacheKey = $this->request->getQuery('key');
			Cache::write('_setup_test_string_' . $cacheKey . '_', time(), $cacheKey);

			$this->Flash->success('Cache written for config ' . $cacheKey);
			return $this->redirect(['action' => 'cache']);
		}

		$configured = Cache::configured();

		$caches = [];
		foreach ($configured as $name) {
			$caches[$name] = Cache::getConfig($name);
		}

		$data = [];
		foreach ($configured as $name) {
			$data[$name] = Cache::read('_setup_test_string_' . $name . '_', $name);
		}

		$this->set(compact('caches', 'data'));
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function database() {
		$Model = TableRegistry::get('Model');
		/** @var \Cake\Database\Connection $db */
		$db = $Model->connection();

		$dbTables = $db->query('SHOW TABLE STATUS');
		$dbTables = (new Collection($dbTables))->toArray();

		$dbSize = 0;
		foreach ($dbTables as $dbTable) {
			$dbSize += $dbTable['Data_length'];
		}

		$this->set(compact('dbTables', 'dbSize'));
	}

}
