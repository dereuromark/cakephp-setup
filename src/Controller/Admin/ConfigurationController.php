<?php

namespace Setup\Controller\Admin;

use App\Controller\AppController;
use Cake\Collection\Collection;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
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
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Flash');
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$Debug = new Debug();
		$uptime = $Debug->getUptime();
		$serverLoad = $Debug->serverLoad();
		$mem = $Debug->getRam();
		$memory = '<i>n/a</i>';
		if ($mem) {
			$memory = '' . $mem['total'] . ' MB total; ' . $mem['free'] . ' MB free';
		}
		$this->set(compact('serverLoad', 'memory', 'uptime'));
	}

}
