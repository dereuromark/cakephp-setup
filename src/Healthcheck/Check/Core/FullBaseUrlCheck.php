<?php

namespace Setup\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class FullBaseUrlCheck extends Check {

	/**
	 * @return void
	 */
	public function check(): void {
		$this->checkFullBaseUrl();
	}

	/**
	 * @return void
	 */
	protected function checkFullBaseUrl(): void {
		$fullBaseUrl = Configure::read('App.fullBaseUrl');
		if (!$fullBaseUrl) {
			$this->failureMessage[] = 'App.fullBaseUrl is not set. Please configure it in your app.php or .env file.';
			$this->passed = false;

			return;
		}

		$this->infoMessage[] = $fullBaseUrl;

		$this->passed = true;
	}

}
