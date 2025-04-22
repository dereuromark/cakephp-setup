<?php

namespace Setup\Controller\Admin;

use App\Controller\AppController;
use Cake\Cache\Cache;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\I18n\DateTime;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use PDO;
use Setup\Utility\Config;
use Setup\Utility\Debug;
use Setup\Utility\OrmTypes;
use Setup\Utility\System;

class BackendController extends AppController {

	/**
	 * @var string|null
	 */
	protected ?string $modelClass = '';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->viewBuilder()->setHelpers(['Tools.Time', 'Tools.Progress']);
		if (Plugin::isLoaded('Templating')) {
			$this->viewBuilder()->addHelper('Templating.IconSnippet');
		} elseif (Plugin::isLoaded('Tools')) {
			$this->viewBuilder()->addHelper('Tools.Format');
		}
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function phpinfo() {
		$this->viewBuilder()->setLayout('ajax');
	}


	/**
	 * @return void
	 */
	public function system() {
		$Debug = new Debug();
		$uploadLimit = $Debug->uploadMaxSize(true);
		$postLimit = $Debug->postMaxSize(true);
		$memoryLimit = $Debug->memoryLimit(true);

		$this->set(compact('uploadLimit', 'postLimit', 'memoryLimit'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function locales() {
		if ($this->request->is('post')) {
			$dateFormat = $this->request->getData('Form.format');
			$locale = $this->request->getData('Form.locale');
			$res = setlocale(LC_TIME, $locale);
			if ($res === false) {
				$this->Flash->warning('Locale not supported');
			}
			$time = new DateTime();
			$result = strftime($dateFormat, (int)$time->toUnixString());
			$this->set(compact('result'));
		} else {
			//FIXME
			//$this->request->data['Form']['format'] = '%A, %B %Y - %H:%M';
		}

		$locales = '0';
		/** @var string $save */
		$save = setlocale(LC_ALL, $locales);
		if (WINDOWS) {
			$localeOptions = ['german', 'english', 'french', 'spanish', 'russian', 'austria', 'switzerland', 'turkish']; # windows
		} else {
			$localeOptions = ['de_DE.utf8', 'de_CH.utf8', 'de_AT.utf8', 'de_BE.utf8', 'de_LU.utf8', 'de_LI.utf8', 'en_US.utf8', 'en_GB.utf8', 'tr_TR.utf8']; # linux
		}

		$localeSettings = [];
		foreach ($localeOptions as $option) {
			$res = setlocale(LC_ALL, $option);
			$content = $res === false ? [] : localeconv();
			$localeSettings[$option] = ['res' => $res, 'content' => $content];
		}

		$System = new System();
		$systemLocales = $System->systemLocales();
		$this->set(compact('localeSettings', 'systemLocales'));

		setlocale(LC_ALL, $save);
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function session() {
		$timestamp = $this->request->getSession()->read('Config.time');

		$time = $timestamp ? (new DateTime())->setTimestamp((int)$timestamp) : new DateTime();

		$sessionConfig = Configure::read('Session');
		$sessionId = $this->request->getSession()->id();
		if ($sessionConfig && $sessionConfig['defaults'] === 'database') {
			$sessionData = TableRegistry::getTableLocator()->get('Sessions')->get($sessionId);
			if ($sessionData->get('data') && is_resource($sessionData->get('data'))) {
				$sessionData->set('data', stream_get_contents($sessionData->get('data')));
			}
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
	public function cookies() {
		$cookies = $this->request->getCookieCollection();

		if ($this->request->is('post')) {
			$name = $this->request->getQuery('cookie');
			if (!$this->request->getCookieCollection()->has($name)) {
				$this->Flash->warning('Cookie already not existing anymore.');

				return $this->redirect([]);
			}
			$cookie = $this->request->getCookieCollection()->get($name);
			$this->response = $this->response->withExpiredCookie($cookie);

			$this->Flash->success('Cookie set as expired.');

			return $this->redirect([]);
		}

		$this->set(compact('cookies'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function cache() {
		if ($this->request->is(['post', 'put'])) {
			/** @var string $cacheKey */
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
		$db = (new Table())->getConnection();

		$dbTables = $db->execute('SHOW TABLE STATUS')->fetchAll(PDO::FETCH_ASSOC);
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

	/**
	 * @return void
	 */
	public function ip() {
		$ipAddress = (string)env('REMOTE_ADDR');
		$host = $ipAddress ? gethostbyaddr($ipAddress) : null;

		$proxyHeaderKeys = [
			'HTTP_VIA',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED',
			'HTTP_CLIENT_IP',
			'HTTP_FORWARDED_FOR_IP',
			'VIA',
			'X_FORWARDED_FOR',
			'FORWARDED_FOR',
			'X_FORWARDED',
			'FORWARDED',
			'CLIENT_IP',
			'FORWARDED_FOR_IP',
			'HTTP_PROXY_CONNECTION',
		];
		$proxyHeaders = [];
		foreach ($proxyHeaderKeys as $proxyHeaderKey) {
			if (isset($_SERVER[$proxyHeaderKey])) {
				$proxyHeaders[$proxyHeaderKey] = $_SERVER[$proxyHeaderKey];
			}
		}

		$this->set(compact('ipAddress', 'host', 'proxyHeaders'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function typeMap() {
		$plugins = Plugin::loaded();
		if ($this->request->getQuery('all')) {
			$plugins = array_keys((array)Configure::read('plugins'));
		}
		$map = OrmTypes::getMap();
		$classes = OrmTypes::getClasses($plugins, $map);

		$this->set(compact('plugins', 'classes', 'map'));
	}

}
