<?php

namespace Setup\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Http\Response;
use Setup\Healthcheck\Healthcheck;
use Setup\Healthcheck\HealthcheckCollector;

/**
 * Healthcheck Component
 *
 * Provides reusable healthcheck functionality for controllers.
 *
 * @method \App\Controller\AppController getController()
 */
class HealthcheckComponent extends Component {

	/**
	 * Run healthcheck and prepare data for controller.
	 *
	 * @param string|null $domain Optional domain filter
	 * @param bool $alwaysShowDetails Always show detailed results (for admin)
	 * @return array{passed: bool, result: \Cake\Collection\CollectionInterface, domains: array<string>, errors: int, warnings: int, executionTime: float}
	 */
	public function run(?string $domain = null, bool $alwaysShowDetails = false): array {
		$healthcheck = new Healthcheck(new HealthcheckCollector());
		$startTime = microtime(true);
		$passed = $healthcheck->run($domain);
		$executionTime = round((microtime(true) - $startTime) * 1000, 2);

		$result = $healthcheck->result();
		$domains = $healthcheck->domains();
		$errors = $healthcheck->errors();
		$warnings = $healthcheck->warnings();

		return compact('passed', 'result', 'domains', 'errors', 'warnings', 'executionTime', 'healthcheck');
	}

	/**
	 * Handle healthcheck response based on request type.
	 *
	 * @param array $data Healthcheck data from run()
	 * @param bool $alwaysShowDetails Always show detailed results (for admin)
	 * @return \Cake\Http\Response|null
	 */
	public function handleResponse(array $data, bool $alwaysShowDetails = false): ?Response {
		$controller = $this->getController();
		$request = $controller->getRequest();
		$response = $controller->getResponse();

		$domain = $request->getQuery('domain') ?: 'all';

		// JSON response
		if ($request->is('json') || $request->getParam('_ext') === 'json') {
			$jsonData = [
				'passed' => $data['passed'],
				'metadata' => [
					'timestamp' => date('c'),
					'execution_time_ms' => $data['executionTime'],
					'total_checks' => $data['result']->unfold()->count(),
					'errors' => $data['errors'],
					'warnings' => $data['warnings'],
					'domain' => $domain,
				],
			];

			if ($alwaysShowDetails || Configure::read('debug')) {
				$jsonData['result'] = $this->formatJsonResult($data['result']);
			}

			return $response->withType('application/json')
				->withStringBody(json_encode($jsonData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		}

		// Simple text response (non-debug, non-admin)
		if (!$alwaysShowDetails && !Configure::read('debug')) {
			return $response->withStringBody($data['passed'] ? 'OK' : 'FAIL')
				->withStatus($data['passed'] ? 200 : 500);
		}

		// Detailed HTML response
		$controller->set([
			'passed' => $data['passed'],
			'result' => $data['result'],
			'domains' => $data['domains'],
			'errors' => $data['errors'],
			'warnings' => $data['warnings'],
		]);

		return null;
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
