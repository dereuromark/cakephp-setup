<?php

namespace Setup\Queue\Task;

use Cake\Collection\CollectionInterface;
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
		$totalCount = $result->unfold()->count();
		$domainCount = count($result);
		$errors = $healthcheck->errors();
		$warnings = $healthcheck->warnings();
		$message = 'Healthcheck queue run ' . ($passed ? 'OK' : 'FAIL');
		$message .= PHP_EOL . PHP_EOL;
		/**
		 * @var string $domain
		 * @var iterable<\Setup\Healthcheck\Check\CheckInterface> $checks
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
				} elseif ($check->successMessage()) {
					$message .= '   Passed: ' . implode(', ', $check->successMessage()) . PHP_EOL;
				}

				if ($check->infoMessage()) {
					$message .= '   Info: ' . implode(', ', $check->infoMessage()) . PHP_EOL;
				}
			}
		}

		$this->io->out('Healthcheck: ' . ($passed ? 'OK' : 'FAIL'));
		$this->io->out(sprintf(
			'Summary: %d check(s) in %d domain(s); errors=%d; warnings=%d.',
			$totalCount,
			$domainCount,
			$errors,
			$warnings,
		));

		$relevantChecks = $this->relevantChecks($result);
		if ($relevantChecks !== []) {
			$this->io->out('Relevant checks:');
			foreach ($relevantChecks as $line) {
				$this->io->out(' - ' . $line);
			}
		}

		$this->io->out('See the configured log target for full healthcheck details.');

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

	/**
	 * @param \Cake\Collection\CollectionInterface<\Setup\Healthcheck\Check\CheckInterface> $result
	 *
	 * @return list<string>
	 */
	protected function relevantChecks(CollectionInterface $result): array {
		$lines = [];

		foreach ($result as $domain => $checks) {
			if (!is_iterable($checks)) {
				continue;
			}

			foreach ($checks as $check) {
				if ($check->passed() && !$check->warningMessage()) {
					continue;
				}

				$status = $check->passed() ? 'WARN' : 'FAIL';
				$detail = '';
				if (!$check->passed() && $check->failureMessage()) {
					$detail = ' - ' . implode(', ', $check->failureMessage());
				} elseif ($check->warningMessage()) {
					$detail = ' - ' . implode(', ', $check->warningMessage());
				}

				$lines[] = $domain . '/' . $check->name() . ': ' . $status . $detail;
			}
		}

		return $lines;
	}

}
