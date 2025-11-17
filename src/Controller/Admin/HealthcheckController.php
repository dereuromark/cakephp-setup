<?php

namespace Setup\Controller\Admin;

use App\Controller\AppController;

/**
 * @property \Setup\Controller\Component\HealthcheckComponent $Healthcheck
 */
class HealthcheckController extends AppController {

	/**
	 * @var string|null
	 */
	protected ?string $defaultTable = '';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Setup.Healthcheck');
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$data = $this->Healthcheck->run($this->request->getQuery('domain'));

		return $this->Healthcheck->handleResponse($data, true);
	}

}
