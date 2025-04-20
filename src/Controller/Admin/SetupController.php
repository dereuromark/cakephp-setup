<?php

namespace Setup\Controller\Admin;

use App\Controller\AppController;
use Setup\Maintenance\Maintenance;

class SetupController extends AppController {

	/**
	 * @var string|null
	 */
	protected ?string $modelClass = '';

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function maintenance() {
		$ip = (string)env('REMOTE_ADDR');

		$maintenance = new Maintenance();
		if ($this->request->is('post')) {
			$enable = (bool)$this->request->getQuery('maintenance');

			$maintenance->addToWhitelist([$ip]);
			$maintenance->setMaintenanceMode($enable);

			return $this->redirect([]);
		}

		$isMaintenanceModeEnabled = $maintenance->isMaintenanceMode();
		$whitelisted = $maintenance->whitelisted($ip);
		$whitelist = $maintenance->whitelist();

		$this->set(compact('ip', 'isMaintenanceModeEnabled', 'whitelisted', 'whitelist'));
	}

}
