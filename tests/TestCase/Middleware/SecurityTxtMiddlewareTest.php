<?php

namespace Setup\Test\TestCase\Middleware;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Setup\Middleware\SecurityTxt;
use Setup\Middleware\SecurityTxtMiddleware;
use Shim\TestSuite\TestCase;

class SecurityTxtMiddlewareTest extends TestCase {

	/**
	 * @return void
	 */
	public function testServesAtWellKnownPath(): void {
		$middleware = new SecurityTxtMiddleware([
			'fields' => ['Contact' => 'https://example.com/security'],
		]);

		$response = $middleware->process($this->request('/.well-known/security.txt'), $this->handler());

		$this->assertSame(200, $response->getStatusCode());
		$this->assertStringContainsString('text/plain', $response->getHeaderLine('Content-Type'));

		$body = (string)$response->getBody();
		$this->assertStringContainsString('Contact: https://example.com/security', $body);
		$this->assertStringContainsString('Expires:', $body);
	}

	/**
	 * @return void
	 */
	public function testExpiresInFuture(): void {
		$middleware = new SecurityTxtMiddleware([
			'fields' => ['Contact' => 'mailto:security@example.com'],
		]);

		$response = $middleware->process($this->request('/.well-known/security.txt'), $this->handler());

		$body = (string)$response->getBody();
		preg_match('/^Expires: (.+)$/m', $body, $matches);
		$this->assertNotEmpty($matches);
		$this->assertGreaterThan(time(), (int)strtotime($matches[1]));
	}

	/**
	 * @return void
	 */
	public function testRootFallbackServed(): void {
		$middleware = new SecurityTxtMiddleware([
			'fields' => ['Contact' => 'mailto:security@example.com'],
		]);

		$response = $middleware->process($this->request('/security.txt'), $this->handler());

		$this->assertSame(200, $response->getStatusCode());
		$this->assertStringNotContainsString('PASSTHROUGH', (string)$response->getBody());
	}

	/**
	 * @return void
	 */
	public function testRootFallbackDisabledPassesThrough(): void {
		$middleware = new SecurityTxtMiddleware([
			'serveRootFallback' => false,
			'fields' => ['Contact' => 'mailto:security@example.com'],
		]);

		$response = $middleware->process($this->request('/security.txt'), $this->handler());

		$this->assertSame('PASSTHROUGH', (string)$response->getBody());
	}

	/**
	 * @return void
	 */
	public function testUnrelatedPathPassesThrough(): void {
		$middleware = new SecurityTxtMiddleware([
			'fields' => ['Contact' => 'mailto:security@example.com'],
		]);

		$response = $middleware->process($this->request('/foo'), $this->handler());

		$this->assertSame('PASSTHROUGH', (string)$response->getBody());
	}

	/**
	 * @return void
	 */
	public function testThrowsWhenContactMissing(): void {
		$this->expectException(InvalidArgumentException::class);

		new SecurityTxtMiddleware();
	}

	/**
	 * @return void
	 */
	public function testThrowsWhenContactEmptyInArray(): void {
		$this->expectException(InvalidArgumentException::class);

		new SecurityTxtMiddleware(['fields' => ['Contact' => '']]);
	}

	/**
	 * @return void
	 */
	public function testAcceptsSecurityTxtDocument(): void {
		$middleware = new SecurityTxtMiddleware(new SecurityTxt(
			contact: 'https://example.com/security/advisories/new',
			canonical: 'https://example.com/.well-known/security.txt',
			preferredLanguages: 'en, de',
		));

		$body = (string)$middleware->process($this->request('/.well-known/security.txt'), $this->handler())->getBody();

		$this->assertStringContainsString('Contact: https://example.com/security/advisories/new', $body);
		$this->assertStringContainsString('Canonical: https://example.com/.well-known/security.txt', $body);
		$this->assertStringContainsString('Preferred-Languages: en, de', $body);
		$this->assertStringContainsString('Expires:', $body);
	}

	/**
	 * @return void
	 */
	public function testDocumentFieldOrder(): void {
		$middleware = new SecurityTxtMiddleware(new SecurityTxt(
			contact: 'mailto:security@example.com',
			canonical: 'https://example.com/.well-known/security.txt',
			preferredLanguages: 'en, de',
			policy: 'https://example.com/policy',
		));

		$body = trim((string)$middleware->process($this->request('/.well-known/security.txt'), $this->handler())->getBody());
		$keys = array_map(fn (string $line): string => explode(':', $line, 2)[0], explode("\n", $body));

		// Actionable fields first, file metadata next, computed Expires last.
		$this->assertSame(
			['Contact', 'Policy', 'Canonical', 'Preferred-Languages', 'Expires'],
			$keys,
		);
	}

	/**
	 * @return void
	 */
	public function testDocumentBehaviorOptionsDisableRootFallback(): void {
		$middleware = new SecurityTxtMiddleware(
			new SecurityTxt(contact: 'mailto:security@example.com'),
			['serveRootFallback' => false],
		);

		$response = $middleware->process($this->request('/security.txt'), $this->handler());

		$this->assertSame('PASSTHROUGH', (string)$response->getBody());
	}

	/**
	 * @return void
	 */
	public function testDocumentBehaviorOptionsCacheMaxAge(): void {
		$middleware = new SecurityTxtMiddleware(
			new SecurityTxt(contact: 'mailto:security@example.com'),
			['cacheMaxAge' => 60],
		);

		$response = $middleware->process($this->request('/.well-known/security.txt'), $this->handler());

		$this->assertSame('max-age=60', $response->getHeaderLine('Cache-Control'));
	}

	/**
	 * @return void
	 */
	public function testMultipleContacts(): void {
		$middleware = new SecurityTxtMiddleware([
			'fields' => ['Contact' => ['https://example.com/s', 'mailto:security@example.com']],
		]);

		$body = (string)$middleware->process($this->request('/.well-known/security.txt'), $this->handler())->getBody();

		$this->assertSame(1, substr_count($body, 'Contact: https://example.com/s'));
		$this->assertSame(1, substr_count($body, 'Contact: mailto:security@example.com'));
	}

	/**
	 * @return void
	 */
	public function testNonGetMethodPassesThrough(): void {
		$middleware = new SecurityTxtMiddleware([
			'fields' => ['Contact' => 'mailto:security@example.com'],
		]);

		$response = $middleware->process($this->request('/.well-known/security.txt', 'POST'), $this->handler());

		$this->assertSame('PASSTHROUGH', (string)$response->getBody());
	}

	/**
	 * @return void
	 */
	public function testContactFirstAndExpiresLast(): void {
		$middleware = new SecurityTxtMiddleware([
			'fields' => [
				'Policy' => 'https://example.com/policy',
				'Contact' => 'mailto:security@example.com',
				'Preferred-Languages' => 'en, de',
			],
		]);

		$body = (string)$middleware->process($this->request('/.well-known/security.txt'), $this->handler())->getBody();

		$this->assertStringStartsWith('Contact: mailto:security@example.com', $body);
		$this->assertStringContainsString('Policy: https://example.com/policy', $body);
		$this->assertStringContainsString('Preferred-Languages: en, de', $body);

		$lines = explode("\n", trim($body));
		$this->assertStringStartsWith('Expires:', (string)end($lines));
	}

	/**
	 * @return void
	 */
	public function testHeadRequestHasHeadersButNoBody(): void {
		$middleware = new SecurityTxtMiddleware([
			'fields' => ['Contact' => 'mailto:security@example.com'],
		]);

		$response = $middleware->process($this->request('/.well-known/security.txt', 'HEAD'), $this->handler());

		$this->assertSame(200, $response->getStatusCode());
		$this->assertStringContainsString('text/plain', $response->getHeaderLine('Content-Type'));
		$this->assertSame('', (string)$response->getBody());
	}

	/**
	 * @return void
	 */
	public function testServedUnderApplicationBasePath(): void {
		$middleware = new SecurityTxtMiddleware([
			'fields' => ['Contact' => 'mailto:security@example.com'],
		]);

		$request = new ServerRequest([
			'url' => '/myapp/.well-known/security.txt',
			'base' => '/myapp',
			'environment' => ['REQUEST_METHOD' => 'GET'],
		]);
		$response = $middleware->process($request, $this->handler());

		$this->assertSame(200, $response->getStatusCode());
		$this->assertStringContainsString('Contact: mailto:security@example.com', (string)$response->getBody());
	}

	/**
	 * @return void
	 */
	public function testCacheControlHeader(): void {
		$middleware = new SecurityTxtMiddleware([
			'cacheMaxAge' => 3600,
			'fields' => ['Contact' => 'mailto:security@example.com'],
		]);

		$response = $middleware->process($this->request('/.well-known/security.txt'), $this->handler());

		$this->assertSame('max-age=3600', $response->getHeaderLine('Cache-Control'));
	}

	/**
	 * Root-relative URI values are resolved to absolute URLs using the request host.
	 *
	 * @return void
	 */
	public function testRelativeUriResolvedToAbsolute(): void {
		$middleware = new SecurityTxtMiddleware(new SecurityTxt(
			contact: '/security',
			canonical: '/.well-known/security.txt',
			preferredLanguages: 'en',
		));

		$body = (string)$middleware->process(
			$this->request('/.well-known/security.txt', host: 'example.org', https: true),
			$this->handler(),
		)->getBody();

		$this->assertStringContainsString('Contact: https://example.org/security', $body);
		$this->assertStringContainsString('Canonical: https://example.org/.well-known/security.txt', $body);
		// Non-URI fields are left untouched.
		$this->assertStringContainsString('Preferred-Languages: en', $body);
	}

	/**
	 * The resolved scheme follows the request (http on a plain-HTTP host).
	 *
	 * @return void
	 */
	public function testRelativeUriUsesRequestScheme(): void {
		$middleware = new SecurityTxtMiddleware([
			'fields' => ['Contact' => '/security'],
		]);

		$body = (string)$middleware->process(
			$this->request('/.well-known/security.txt', host: 'example.org'),
			$this->handler(),
		)->getBody();

		$this->assertStringContainsString('Contact: http://example.org/security', $body);
	}

	/**
	 * Already-absolute values (https/mailto/tel) pass through unchanged.
	 *
	 * @return void
	 */
	public function testAbsoluteUriLeftUnchanged(): void {
		$middleware = new SecurityTxtMiddleware([
			'fields' => ['Contact' => ['mailto:security@example.com', 'https://example.com/report']],
		]);

		$body = (string)$middleware->process(
			$this->request('/.well-known/security.txt', host: 'other.example'),
			$this->handler(),
		)->getBody();

		$this->assertStringContainsString('Contact: mailto:security@example.com', $body);
		$this->assertStringContainsString('Contact: https://example.com/report', $body);
		$this->assertStringNotContainsString('other.example', $body);
	}

	/**
	 * With no explicit host the CakePHP request still carries a default authority
	 * (`localhost`), which is used to resolve the relative value.
	 *
	 * @return void
	 */
	public function testRelativeUriUsesDefaultHost(): void {
		$middleware = new SecurityTxtMiddleware([
			'fields' => ['Contact' => '/security'],
		]);

		$body = (string)$middleware->process($this->request('/.well-known/security.txt'), $this->handler())->getBody();

		$this->assertStringContainsString('Contact: http://localhost/security', $body);
	}

	/**
	 * Build a request for the given path and method.
	 *
	 * @param string $path
	 * @param string $method
	 * @param string|null $host Optional `HTTP_HOST` so the request has an authority.
	 * @param bool $https Whether the request is served over HTTPS.
	 *
	 * @return \Cake\Http\ServerRequest
	 */
	protected function request(string $path, string $method = 'GET', ?string $host = null, bool $https = false): ServerRequest {
		$environment = ['REQUEST_METHOD' => $method];
		if ($host !== null) {
			$environment['HTTP_HOST'] = $host;
		}
		if ($https) {
			$environment['HTTPS'] = 'on';
		}

		return new ServerRequest([
			'url' => $path,
			'environment' => $environment,
		]);
	}

	/**
	 * A pass-through handler returning a sentinel response.
	 *
	 * @return \Psr\Http\Server\RequestHandlerInterface
	 */
	protected function handler(): RequestHandlerInterface {
		return new class implements RequestHandlerInterface {

			/**
			 * @param \Psr\Http\Message\ServerRequestInterface $request
			 * @return \Psr\Http\Message\ResponseInterface
			 */
			public function handle(ServerRequestInterface $request): ResponseInterface {
				return (new Response())->withStringBody('PASSTHROUGH');
			}

		};
	}

}
