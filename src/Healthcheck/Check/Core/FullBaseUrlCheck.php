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

		// Web-only runtime check: Test if fullBaseUrl is being set from HTTP_HOST
		if (PHP_SAPI !== 'cli' && $this->isVulnerableToHostHeaderInjection($fullBaseUrl)) {
			$this->failureMessage[] = 'CRITICAL: App.fullBaseUrl appears to be dynamically set from the HTTP Host header!';
			$this->failureMessage[] = 'This makes your application vulnerable to Host Header Injection attacks.';
			$this->failureMessage[] = 'Hardcode App.fullBaseUrl in config/app.php or set APP_FULL_BASE_URL environment variable.';
			$this->passed = false;
			$this->addFixInstructions();

			return;
		}

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
	 * Runtime check to detect if fullBaseUrl is being set from HTTP_HOST header
	 *
	 * @param string $fullBaseUrl The configured fullBaseUrl
	 * @return bool True if vulnerable (fullBaseUrl matches HTTP_HOST)
	 */
	protected function isVulnerableToHostHeaderInjection(string $fullBaseUrl): bool {
		$httpHost = env('HTTP_HOST');
		if (!$httpHost || !is_string($httpHost)) {
			return false;
		}

		// Extract host from fullBaseUrl
		$parsedUrl = parse_url($fullBaseUrl);
		if (!$parsedUrl || !isset($parsedUrl['host'])) {
			return false;
		}

		// Build the host string from parsed URL (with port if present)
		$configuredHost = $parsedUrl['host'];
		if (isset($parsedUrl['port'])) {
			$scheme = $parsedUrl['scheme'] ?? 'http';
			$isDefaultPort = ($scheme === 'https' && $parsedUrl['port'] === 443) ||
			($scheme === 'http' && $parsedUrl['port'] === 80);

			if (!$isDefaultPort) {
				$configuredHost .= ':' . $parsedUrl['port'];
			}
		}

		// If the configured host exactly matches HTTP_HOST, it's likely being set dynamically
		if (strcasecmp($configuredHost, $httpHost) === 0) {
			return true;
		}

		return false;
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
