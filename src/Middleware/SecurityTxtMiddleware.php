<?php

namespace Setup\Middleware;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\Response;
use InvalidArgumentException;
use Laminas\Diactoros\CallbackStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Serves an RFC 9116 `security.txt` file.
 *
 * The `Expires` field is computed on every request from `expiresInterval`, so it
 * never goes stale. `Contact` is required by the RFC; constructing the middleware
 * without one throws an `InvalidArgumentException` (fail fast at boot rather than
 * serving an invalid file).
 *
 * Add it early in the middleware queue (before routing/auth). Pass a
 * {@see \Setup\Middleware\SecurityTxt} document (preferred):
 *
 * ```php
 * $middlewareQueue->add(new \Setup\Middleware\SecurityTxtMiddleware(
 *     new \Setup\Middleware\SecurityTxt(
 *         contact: 'https://github.com/owner/repo/security/advisories/new',
 *         canonical: 'https://example.com/.well-known/security.txt',
 *         preferredLanguages: 'en, de',
 *     ),
 *     // optional behavior: ['path' => ..., 'serveRootFallback' => ..., 'cacheMaxAge' => ...]
 * ));
 * ```
 *
 * A raw config array is still accepted as an escape hatch (e.g. for fields not
 * covered by the value object):
 *
 * ```php
 * $middlewareQueue->add(new \Setup\Middleware\SecurityTxtMiddleware([
 *     'fields' => ['Contact' => 'mailto:security@example.com'],
 * ]));
 * ```
 */
class SecurityTxtMiddleware implements MiddlewareInterface {

	use InstanceConfigTrait;

	/**
	 * Default `Cache-Control` max-age in seconds (1 day).
	 *
	 * @var int
	 */
	protected const DEFAULT_CACHE_MAX_AGE = 86400;

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'path' => '/.well-known/security.txt',
		'serveRootFallback' => true,
		'expiresInterval' => '+1 year',
		'cacheMaxAge' => self::DEFAULT_CACHE_MAX_AGE,
		'fields' => [],
	];

	/**
	 * @param \Setup\Middleware\SecurityTxt|array<string, mixed> $config A security.txt document (preferred), or a raw config array (escape hatch).
	 * @param array<string, mixed> $options Behavior options (`path`, `serveRootFallback`, `cacheMaxAge`) when passing a document; ignored for the array form.
	 *
	 * @throws \InvalidArgumentException If no non-empty `Contact` is configured.
	 */
	public function __construct(SecurityTxt|array $config = [], array $options = []) {
		if ($config instanceof SecurityTxt) {
			$config = $options + [
				'fields' => $config->toFields(),
				'expiresInterval' => $config->expiresInterval,
			];
		}

		$this->setConfig($config);

		/** @var array<string, mixed> $fields */
		$fields = (array)$this->getConfig('fields');
		if (SecurityTxt::normalize($fields['Contact'] ?? null) === []) {
			throw new InvalidArgumentException(
				'SecurityTxtMiddleware requires at least one non-empty `Contact` field (RFC 9116).',
			);
		}
	}

	/**
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @param \Psr\Http\Server\RequestHandlerInterface $handler
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$method = $request->getMethod();
		if (!in_array($method, ['GET', 'HEAD'], true)) {
			return $handler->handle($request);
		}

		if (!$this->matchesPath($this->relativePath($request))) {
			return $handler->handle($request);
		}

		/** @var array<string, mixed> $fields */
		$fields = $this->absolutize((array)$this->getConfig('fields'), $request);
		$contacts = SecurityTxt::normalize($fields['Contact'] ?? null);

		$response = $this->build($fields, $contacts);

		// HEAD must return the same headers as GET but without a body.
		if ($method === 'HEAD') {
			$response = $response->withBody(new CallbackStream(static fn (): string => ''));
		}

		return $response;
	}

	/**
	 * Resolve the request path relative to the application base path, so the
	 * middleware also matches when the app is mounted in a subdirectory.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @return string
	 */
	protected function relativePath(ServerRequestInterface $request): string {
		$path = $request->getUri()->getPath();
		$base = (string)$request->getAttribute('base', '');
		if ($base !== '' && str_starts_with($path, $base)) {
			$path = substr($path, strlen($base));
		}

		return $path !== '' ? $path : '/';
	}

	/**
	 * Resolve any root-relative URI field values (e.g. `/security`) to absolute
	 * URLs using the current request's scheme and host, so the served file stays
	 * RFC 9116 compliant (absolute URIs) while the configured values can stay
	 * host-agnostic and travel across deploys unchanged.
	 *
	 * Values that are already absolute (`https:`, `mailto:`, `tel:`, ...) or
	 * protocol-relative (`//host/...`) are left untouched. `Expires` and
	 * `Preferred-Languages` are never URIs, so they are skipped.
	 *
	 * @param array<string, mixed> $fields
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @return array<string, mixed>
	 */
	protected function absolutize(array $fields, ServerRequestInterface $request): array {
		$uri = $request->getUri();
		$authority = $uri->getAuthority();
		if ($authority === '') {
			return $fields;
		}

		$base = $uri->getScheme() . '://' . $authority;
		foreach ($fields as $name => $value) {
			if ($name === 'Expires' || $name === 'Preferred-Languages') {
				continue;
			}

			$fields[$name] = $this->absolutizeValue($value, $base);
		}

		return $fields;
	}

	/**
	 * Prefix a single root-relative URI value (or each value in a list) with the
	 * absolute base; pass through anything that is not root-relative.
	 *
	 * @param mixed $value
	 * @param string $base
	 * @return mixed
	 */
	protected function absolutizeValue(mixed $value, string $base): mixed {
		if (is_array($value)) {
			return array_map(fn ($item): mixed => $this->absolutizeValue($item, $base), $value);
		}

		if (is_string($value) && str_starts_with($value, '/') && !str_starts_with($value, '//')) {
			return $base . $value;
		}

		return $value;
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	protected function matchesPath(string $path): bool {
		if ($path === $this->getConfig('path')) {
			return true;
		}

		return $this->getConfig('serveRootFallback') && $path === '/security.txt';
	}

	/**
	 * @param array<string, mixed> $fields
	 * @param array<string> $contacts
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	protected function build(array $fields, array $contacts): ResponseInterface {
		$lines = [];
		foreach ($contacts as $value) {
			$lines[] = 'Contact: ' . $value;
		}
		foreach ($fields as $name => $value) {
			if ($name === 'Contact' || $name === 'Expires') {
				continue;
			}
			foreach (SecurityTxt::normalize($value) as $item) {
				$lines[] = $name . ': ' . $item;
			}
		}
		$lines[] = 'Expires: ' . $this->expires();

		$body = implode("\n", $lines) . "\n";

		$response = (new Response())
			->withType('text/plain')
			->withStringBody($body);

		$cacheMaxAge = (int)$this->getConfig('cacheMaxAge');
		if ($cacheMaxAge > 0) {
			$response = $response->withHeader('Cache-Control', 'max-age=' . $cacheMaxAge);
		}

		return $response;
	}

	/**
	 * Compute the RFC 9116 `Expires` value as a UTC ISO-8601 timestamp.
	 *
	 * @return string
	 */
	protected function expires(): string {
		$interval = (string)$this->getConfig('expiresInterval');
		$timestamp = strtotime($interval);
		if ($timestamp === false) {
			$timestamp = (int)strtotime('+1 year');
		}

		return gmdate('Y-m-d\TH:i:s.000\Z', $timestamp);
	}

}
