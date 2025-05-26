<?php

namespace Setup\Queue\Task;

use Cake\Log\Log;
use Queue\Queue\AddFromBackendInterface;
use Queue\Queue\AddInterface;
use Queue\Queue\Task;
use Setup\Healthcheck\Healthcheck;
use Setup\Healthcheck\HealthcheckCollector;

class HealthcheckTask extends Task implements AddInterface, AddFromBackendInterface {

	/**
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$healthcheck = new Healthcheck(new HealthcheckCollector());

		$passed = $healthcheck->run($data['domain'] ?? null);

		$result = $healthcheck->result();
		$message = 'Healthcheck queue run ' . ($passed ? 'OK' : 'FAIL');
		$message .= PHP_EOL . PHP_EOL;
		/**
		 * @var string $domain
		 * @var \Setup\Healthcheck\Check\CheckInterface[] $checks
		 */
		foreach ($result as $domain => $checks) {
			$message .= '### ' . $domain . PHP_EOL;
			foreach ($checks as $check) {
				$message .= ' - ' . $check->name() . ': ' . ($check->passed() ? 'OK' : 'FAIL') . PHP_EOL;
				if (!$check->passed()) {
					if ($check->failureMessage()) {
						$message .= '   Error: ' . implode(', ', $check->failureMessage()) . PHP_EOL;
					}
					if ($check->warningMessage()) {
						$message .= '   Warning: ' . implode(', ', $check->warningMessage()) . PHP_EOL;
					}
				} else {
					if ($check->successMessage()) {
						$message .= '   Passed: ' . implode(', ', $check->successMessage()) . PHP_EOL;
					}
				}

				if ($check->infoMessage()) {
					$message .= '   Info: ' . implode(', ', $check->infoMessage()) . PHP_EOL;
				}
			}
		}

		Log::write($passed ? 'info' : 'warning', $message);
	}

	/**
	 * @param string|null $data
	 * @return void
	 */
	public function add(?string $data): void {
		$data = [
			'domain' => $data,
		];
		$this->QueuedJobs->createJob('Setup.Healthcheck', $data);
		$this->io->success('OK, job created, now run the worker');
	}

}
