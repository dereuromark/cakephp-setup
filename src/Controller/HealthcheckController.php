<?php

namespace Setup\Controller;

use App\Controller\AppController;
use Cake\Event\EventInterface;

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
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);

		if ($this->components()->has('Auth') && method_exists($this->components()->get('Auth'), 'allow')) {
			$this->components()->get('Auth')->allow();
		} elseif ($this->components()->has('Authentication') && method_exists($this->components()->get('Authentication'), 'allowUnauthenticated')) {
			$this->components()->get('Authentication')->allowUnauthenticated(['index']);
		}
		if ($this->components()->has('Authorization') && method_exists($this->components()->get('Authorization'), 'skipAuthorization')) {
			$this->components()->get('Authorization')->skipAuthorization();
		}
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$data = $this->Healthcheck->run($this->request->getQuery('domain'));

		return $this->Healthcheck->handleResponse($data);
	}

}
