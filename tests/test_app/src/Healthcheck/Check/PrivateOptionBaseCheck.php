<?php

namespace TestApp\Healthcheck\Check;

use Setup\Healthcheck\Check\Check;

abstract class PrivateOptionBaseCheck extends Check {

	public function __construct(private bool $outcome) {
	}

	public function check(): void {
		$this->passed = $this->outcome;
	}

}
