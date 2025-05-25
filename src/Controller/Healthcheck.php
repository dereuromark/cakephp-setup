<?php

namespace Setup\Controller;

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Setup\Healthcheck\Check\CheckInterface;
use Setup\Healthcheck\HealthcheckCollector;

class Healthcheck {

	/**
	 * @var \Setup\Healthcheck\HealthcheckCollector
	 */
	protected HealthcheckCollector $collector;

	protected bool $passed = true;

	protected CollectionInterface $result;

	/**
	 * @param \Setup\Healthcheck\HealthcheckCollector $collector
	 */
	public function __construct(HealthcheckCollector $collector) {
		$this->collector = $collector;
		$this->result = new Collection([]);
	}

	/**
	 * @param string|null $domain
	 * @return bool
	 */
	public function run(?string $domain = null): bool {
		$checks = $this->collector->getChecks();
		foreach ($checks as $check) {
			if ($domain && $check->domain() !== $domain) {
				continue;
			}

			$check->check();
			if (!$check->passed()) {
				$this->passed = false;
			}

			$this->result = $this->result->appendItem($check);
		}

		return $this->passed;
	}

	/**
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function result(): CollectionInterface {
		return $this->result->groupBy(function (CheckInterface $result) {
			return $result->domain();
		});
	}

	/**
	 * @return array<string>
	 */
	public function domains(): array {
		return $this->collector->getDomains();
	}

}
