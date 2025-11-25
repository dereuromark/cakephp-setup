<?php

namespace Setup\Healthcheck\Check\Core;

use Cake\Core\Configure;
use Setup\Healthcheck\Check\Check;

class SecurityHeadersCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the application is using HTTPS and has security headers configured in production.';

	protected bool $isDebug;

	protected string $level = self::LEVEL_WARNING;

	/**
	 * @var array<string>
	 */
	protected array $scope = [
		self::SCOPE_WEB,
	];

	public function __construct() {
		$this->isDebug = (bool)Configure::read('debug');
	}

	/**
	 * @return void
	 */
	public function check(): void {
		// In development mode, HTTPS is optional
		if ($this->isDebug) {
			$this->passed = true;
			$this->infoMessage[] = 'HTTPS and security headers are optional in development mode.';

			return;
		}

		$this->passed = true;

		// Check for HTTPS
		$this->checkHttps();

		// Check for security headers (if running in web context with headers available)
		if (function_exists('headers_list')) {
			$this->checkSecurityHeaders();
		}

		if (!$this->passed) {
			$this->addFixInstructions();
		}
	}

	/**
	 * Check if the application is running on HTTPS.
	 *
	 * @return void
	 */
	protected function checkHttps(): void {
		$isHttps = false;

		// Check various HTTPS indicators
		if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
			$isHttps = true;
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
			$isHttps = true;
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
			$isHttps = true;
		} elseif (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
			$isHttps = true;
		}

		if (!$isHttps) {
			$this->warningMessage[] = 'Application is not running on HTTPS. In production, always use HTTPS to protect user data.';
			$this->passed = false;
		} else {
			$this->infoMessage[] = 'Application is running on HTTPS.';
		}
	}

	/**
	 * Check for recommended security headers.
	 *
	 * @return void
	 */
	protected function checkSecurityHeaders(): void {
		$headers = headers_list();
		$headerMap = [];

		foreach ($headers as $header) {
			$parts = explode(':', $header, 2);
			if (count($parts) === 2) {
				$headerMap[strtolower(trim($parts[0]))] = trim($parts[1]);
			}
		}

		// Check for X-Frame-Options
		if (!isset($headerMap['x-frame-options'])) {
			$this->infoMessage[] = 'X-Frame-Options header not set. Consider setting to DENY or SAMEORIGIN to prevent clickjacking.';
		}

		// Check for X-Content-Type-Options
		if (!isset($headerMap['x-content-type-options'])) {
			$this->infoMessage[] = 'X-Content-Type-Options header not set. Consider setting to "nosniff" to prevent MIME type sniffing.';
		}

		// Check for Strict-Transport-Security (HSTS)
		if (!isset($headerMap['strict-transport-security'])) {
			$this->infoMessage[] = 'Strict-Transport-Security (HSTS) header not set. Consider adding for HTTPS enforcement.';
		}

		// Check for Content-Security-Policy
		if (!isset($headerMap['content-security-policy'])) {
			$this->infoMessage[] = 'Content-Security-Policy header not set. Consider adding CSP headers to prevent XSS attacks.';
		}

		// Check for X-XSS-Protection (legacy but still useful)
		if (!isset($headerMap['x-xss-protection'])) {
			$this->infoMessage[] = 'X-XSS-Protection header not set. Consider setting to "1; mode=block" for older browsers.';
		}

		// Check for Referrer-Policy
		if (!isset($headerMap['referrer-policy'])) {
			$this->infoMessage[] = 'Referrer-Policy header not set. Consider setting to "strict-origin-when-cross-origin" or "no-referrer" to control referrer information.';
		}

		// Check for Permissions-Policy (formerly Feature-Policy)
		if (!isset($headerMap['permissions-policy'])) {
			$this->infoMessage[] = 'Permissions-Policy header not set. Consider restricting browser features like camera, microphone, geolocation.';
		}
	}

	/**
	 * Add helpful information about security best practices.
	 *
	 * @return void
	 */
	protected function addFixInstructions(): void {
		$this->infoMessage[] = 'To improve security:';
		$this->infoMessage[] = '1. Enable HTTPS:';
		$this->infoMessage[] = '   - Obtain SSL/TLS certificate (Let\'s Encrypt, commercial CA)';
		$this->infoMessage[] = '   - Configure web server (Apache/Nginx) for HTTPS';
		$this->infoMessage[] = '   - Redirect HTTP to HTTPS';
		$this->infoMessage[] = '2. Add security headers in your web server config or middleware:';
		$this->infoMessage[] = '   X-Frame-Options: DENY';
		$this->infoMessage[] = '   X-Content-Type-Options: nosniff';
		$this->infoMessage[] = '   Strict-Transport-Security: max-age=31536000; includeSubDomains';
		$this->infoMessage[] = '   Content-Security-Policy: default-src \'self\'';
		$this->infoMessage[] = '   X-XSS-Protection: 1; mode=block';
		$this->infoMessage[] = '   Referrer-Policy: strict-origin-when-cross-origin';
		$this->infoMessage[] = '   Permissions-Policy: geolocation=(), camera=(), microphone=()';
		$this->infoMessage[] = '3. For CakePHP, use SecurityHeadersMiddleware or configure in app.php';
	}

}
