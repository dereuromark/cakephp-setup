<?php

namespace Setup\Controller\Admin;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Psr\Http\Message\ResponseInterface;

class UptimeController extends AppController {

	/**
	 * @var string|null
	 */
	protected ?string $defaultTable = '';

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);
		if ($event->getResult() instanceof ResponseInterface || $event->isStopped()) {
			if ($this->components()->has('Authorization') && method_exists($this->components()->get('Authorization'), 'skipAuthorization')) {
				$this->components()->get('Authorization')->skipAuthorization();
			}

			return;
		}

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
	 * @return \Cake\Http\Response|null
	 */
	public function index() {
		$key = $this->request->getQuery('key');
		if ($key === null) {
			$response = 'OK';
		} elseif (is_string($key) && preg_match('/^[a-f0-9]{32}$/', $key)) {
			$response = $key;
		} else {
			$response = '';
		}

		return $this->response->withStringBody($response);
	}

}
