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
			$this->failureMessage[] = 'App.fullBaseUrl is not set. This leaves your application vulnerable to Host Header Injection attacks!';
			$this->failureMessage[] = 'Attackers can hijack password reset tokens and other sensitive URL-based operations.';
			$this->passed = false;
			$this->addFixInstructions();

			return;
		}

		$this->infoMessage[] = $fullBaseUrl;

		$isDebug = Configure::read('debug');
		if (!$isDebug) {
			if (!str_starts_with($fullBaseUrl, 'https://')) {
				$this->warningMessage[] = 'App.fullBaseUrl should use HTTPS in production: ' . $fullBaseUrl;
			}

			if (preg_match('#^https?://(localhost|127\.0\.0\.1|0\.0\.0\.0)(:|/|$)#i', $fullBaseUrl)) {
				$this->warningMessage[] = 'App.fullBaseUrl is using localhost in production. This should be your actual domain.';
			}
		}

		$this->passed = true;
	}

	/**
	 * Add helpful information about how to set App.fullBaseUrl.
	 *
	 * @return void
	 */
	protected function addFixInstructions(): void {
		$this->infoMessage[] = 'SECURITY: Setting App.fullBaseUrl prevents Host Header Injection attacks that can compromise password resets.';
		$this->infoMessage[] = 'Set App.fullBaseUrl in one of these locations:';
		$this->infoMessage[] = '1. In .env file: APP_FULL_BASE_URL=https://example.com';
		$this->infoMessage[] = '2. In config/app.php or config/app_local.php: \'App\' => [\'fullBaseUrl\' => \'https://example.com\']';
		$this->infoMessage[] = 'Use your actual domain name (e.g., https://yourdomain.com)';
		$this->infoMessage[] = 'This is required for generating absolute URLs in emails, CLI commands, and other contexts.';
	}

}
