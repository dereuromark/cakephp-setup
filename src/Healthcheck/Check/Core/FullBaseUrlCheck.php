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
			$this->addFixInstructions();

			return;
		}

		$this->infoMessage[] = $fullBaseUrl;

		$this->passed = true;
	}

	/**
	 * Add helpful information about how to set App.fullBaseUrl.
	 *
	 * @return void
	 */
	protected function addFixInstructions(): void {
		$this->infoMessage[] = 'Set App.fullBaseUrl in one of these locations:';
		$this->infoMessage[] = '1. In .env file: APP_FULL_BASE_URL=https://example.com';
		$this->infoMessage[] = '2. In config/app.php or config/app_local.php: \'App\' => [\'fullBaseUrl\' => \'https://example.com\']';
		$this->infoMessage[] = 'Use your actual domain name (e.g., https://yourdomain.com)';
		$this->infoMessage[] = 'This is required for generating absolute URLs in emails, CLI commands, and other contexts.';
	}

}
