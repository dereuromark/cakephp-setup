<?php

namespace Setup\Controller\Admin;

use App\Controller\AppController;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use PDO;
use Setup\Utility\Debug;
use Setup\Utility\System;

/**
 * @property \Tools\Controller\Component\CommonComponent $Common
 * @property \Setup\Utility\System $System
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class ConfigurationController extends AppController {

	/**
	 * @var string|null
	 */
	protected ?string $defaultTable = '';

	/**
	 * @var \Setup\Utility\Debug
	 */
	protected $Debug;

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Flash');
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return \Cake\Http\Response|null|void
	 */
	public function beforeFilter(EventInterface $event) {
		$this->Debug = new Debug();

		return parent::beforeFilter($event);
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$uptime = $this->Debug->getUptime();
		$serverLoad = $this->Debug->serverLoad();
		$mem = $this->Debug->getRam();
		$memory = '<i>n/a</i>';
		if ($mem) {
			$memory = '' . $mem['total'] . ' MB total; ' . $mem['free'] . ' MB free';
		}
		$this->set(compact('serverLoad', 'memory', 'uptime'));
	}

	/**
	 * @return void
	 */
	public function system() {
		$uploadLimit = $this->Debug->uploadMaxSize(true);
		$postLimit = $this->Debug->postMaxSize(true);
		$memoryLimit = $this->Debug->memoryLimit(true);

		$this->set(compact('uploadLimit', 'postLimit', 'memoryLimit'));
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

		$time = new DateTime($timestamp);

		$sessionConfig = Configure::read('Session');
		$sessionId = $this->request->getSession()->id();
		if ($sessionConfig['defaults'] === 'database') {
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
	public function diskSpace() {
		$this->System = new System();

		$freeSpace = $this->System->freeDiskSpace();

		$appPath = ROOT . DS;
		$space = [];
		$space['app'] = $this->System->diskSpace($appPath);

		$this->set(compact('freeSpace', 'space', 'appPath'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function locales() {
		if ($this->Common->isPosted()) {
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

		$this->System = new System();
		$systemLocales = $this->System->systemLocales();
		$this->set(compact('localeSettings', 'systemLocales'));

		setlocale(LC_ALL, $save);
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function database() {
		$Model = $this->fetchTable('Sessions');
		/** @var \Cake\Database\Connection $db */
		$db = $Model->getConnection();

		/** @var iterable $dbTables */
		$dbTables = $db->execute('SHOW TABLE STATUS')->fetchAll(PDO::FETCH_ASSOC);
		$dbTables = (new Collection($dbTables))->toArray();

		$dbSize = 0;
		foreach ($dbTables as $dbTable) {
			$dbSize += $dbTable['Data_length'];
		}

		$this->set(compact('dbTables', 'dbSize'));
	}

}
