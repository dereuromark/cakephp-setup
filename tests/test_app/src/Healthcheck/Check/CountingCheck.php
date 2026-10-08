<?php

namespace TestApp\Healthcheck\Check;

use Setup\Healthcheck\Check\Check;

class CountingCheck extends Check {

	public static int $runs = 0;

	public ?object $options = null;

	public function __construct(protected string $testDomain = 'Test', protected bool $outcome = false, protected bool $warn = false) {
	}

	public function domain(): string {
		return $this->testDomain;
	}

	public function check(): void {
		static::$runs++;
		$this->passed = $this->outcome;
		$this->failureMessage = $this->outcome ? [] : ['Failed'];
		$this->successMessage = $this->outcome ? ['Passed'] : [];
		$this->warningMessage = $this->warn ? ['Warning'] : [];
		$this->infoMessage = ['Run ' . static::$runs];
	}

}
