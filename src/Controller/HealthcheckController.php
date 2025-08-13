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
		$startTime = microtime(true);
		$passed = $healthcheck->run($this->request->getQuery('domain'));
		$executionTime = round((microtime(true) - $startTime) * 1000, 2);

		if ($this->request->is('json') || $this->request->getParam('_ext') === 'json') {
			$data = [
				'passed' => $passed,
				'metadata' => [
					'timestamp' => date('c'),
					'execution_time_ms' => $executionTime,
					'total_checks' => $healthcheck->result()->unfold()->count(),
					'errors' => $healthcheck->errors(),
					'warnings' => $healthcheck->warnings(),
					'domain' => $this->request->getQuery('domain') ?: 'all',
				],
			];
			if (Configure::read('debug')) {
				$data['result'] = $this->formatJsonResult($healthcheck->result());
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
		$errors = $healthcheck->errors();
		$warnings = $healthcheck->warnings();
		$this->set(compact('passed', 'result', 'domains', 'errors', 'warnings'));
	}

	/**
	 * Format the result for JSON response with additional metadata.
	 *
	 * @param \Cake\Collection\CollectionInterface $result
	 * @return array
	 */
	protected function formatJsonResult($result): array {
		$formatted = [];
		foreach ($result as $domain => $checks) {
			$formattedChecks = [];
			foreach ($checks as $check) {
				$formattedChecks[] = [
					'name' => $check->name(),
					'passed' => $check->passed(),
					'level' => $check->level(),
					'priority' => $check->priority(),
					'domain' => $check->domain(),
					'messages' => [
						'success' => $check->successMessage(),
						'warning' => $check->warningMessage(),
						'failure' => $check->failureMessage(),
						'info' => $check->infoMessage(),
					],
				];
			}
			$formatted[$domain] = $formattedChecks;
		}

		return $formatted;
	}

}
