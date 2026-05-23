<?php

namespace Setup\Middleware;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\Response;
use Laminas\Diactoros\CallbackStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Serves an RFC 9116 `security.txt` file.
 *
 * The `Expires` field is computed on every request from `expiresInterval`, so it
 * never goes stale. `Contact` is required by the RFC; if none is configured the
 * middleware passes through instead of emitting an invalid file.
 *
 * Add it early in the middleware queue (before routing/auth):
 *
 * ```php
 * $middlewareQueue->add(new \Setup\Middleware\SecurityTxtMiddleware([
 *     'fields' => [
 *         'Contact' => 'https://github.com/owner/repo/security/advisories/new',
 *         'Canonical' => 'https://example.com/.well-known/security.txt',
 *         'Preferred-Languages' => 'en, de',
 *     ],
 * ]));
 * ```
 */
class SecurityTxtMiddleware implements MiddlewareInterface {

	use InstanceConfigTrait;

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'path' => '/.well-known/security.txt',
		'serveRootFallback' => true,
		'expiresInterval' => '+1 year',
		'cacheMaxAge' => DAY,
		'fields' => [],
	];

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$this->setConfig($config);
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
		$fields = (array)$this->getConfig('fields');
		$contacts = $this->normalizeValues($fields['Contact'] ?? null);
		if (!$contacts) {
			return $handler->handle($request);
		}

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
			foreach ($this->normalizeValues($value) as $item) {
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

	/**
	 * Normalize a field value (string or list) into a list of non-empty trimmed strings.
	 *
	 * @param mixed $value
	 * @return array<string>
	 */
	protected function normalizeValues($value): array {
		if ($value === null || $value === '' || $value === []) {
			return [];
		}

		$values = is_array($value) ? $value : [$value];
		$result = [];
		foreach ($values as $item) {
			$item = trim((string)$item);
			if ($item !== '') {
				$result[] = $item;
			}
		}

		return $result;
	}

}
