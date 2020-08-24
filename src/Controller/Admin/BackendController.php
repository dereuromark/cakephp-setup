<?php

namespace Setup\Controller\Admin;

use App\Controller\AppController;
use Cake\Cache\Cache;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Setup\Utility\Config;

class BackendController extends AppController {

	/**
	 * @var string|null
	 */
	protected $modelClass = '';

	/**
	 * @return void
	 */
	public function initialize(): void {
		$this->viewBuilder()->setHelpers(['Tools.Time', 'Tools.Format', 'Tools.Progress']);
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function phpinfo() {
		$this->viewBuilder()->setLayout('ajax');
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function session() {
		$timestamp = $this->request->getSession()->read('Config.time');

		$time = new Time($timestamp);

		$sessionConfig = Configure::read('Session');
		$sessionId = $this->request->getSession()->id();
		if ($sessionConfig && $sessionConfig['defaults'] === 'database') {
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
	 * @return \Cake\Http\Response|null|void
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
	 * @return \Cake\Http\Response|null|void
	 */
	public function database() {
		$Model = TableRegistry::get('Model');
		/** @var \Cake\Database\Connection $db */
		$db = $Model->getConnection();

		$dbTables = $db->query('SHOW TABLE STATUS');
		$dbTables = (new Collection($dbTables))->toArray();

		$dbSizes = [];
		foreach ($dbTables as $key => $dbTable) {
			if (preg_match('/phinxlog$/', $dbTable['Name'])) {
				unset($dbTables[$key]);

				continue;
			}

			$dbSizes[] = $dbTable['Data_length'];
		}
		$dbSize = array_sum($dbSizes);
		$maxSize = $dbSizes ? max($dbSizes) : 0;

		$this->set(compact('dbTables', 'dbSize', 'maxSize'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function env() {
		$envVars = Config::getEnvVars();

		$localConfig = Config::getLocal();

		$this->set(compact('envVars', 'localConfig'));
	}

}
