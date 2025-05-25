<?php

namespace Setup\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Setup\Healthcheck\Healthcheck;
use Setup\Healthcheck\HealthcheckCollector;

class HealthcheckController extends AppController {

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
		$healthcheck = new Healthcheck(new HealthcheckCollector());
		$passed = $healthcheck->run($this->request->getQuery('domain'));

		if ($this->request->is('json')) {
			$data = [
				'passed' => $passed,
			];
			if (Configure::read('debug')) {
				$data['result'] = $healthcheck->result();
			}

			return $this->response->withType('application/json')
				->withStringBody(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		}

		if (!Configure::read('debug')) {
			return $this->response->withStringBody($passed ? 'OK' : 'FAIL')
				->withStatus($passed ? 200 : 500);
		}

		$result = $healthcheck->result();
		$domains = $healthcheck->domains();
		$this->set(compact('passed', 'result', 'domains'));
	}

}
