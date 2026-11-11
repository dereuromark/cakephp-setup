<?php

namespace Setup\Test\TestCase\Controller\Admin;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\MiddlewareQueue;
use Cake\TestSuite\IntegrationTestTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Setup\TestSuite\DriverSkipTrait;
use Shim\TestSuite\TestCase;
use Templating\View\Icon\BootstrapIcon;

/**
 * @uses \Setup\Controller\Admin\BackendController
 */
class BackendControllerTest extends TestCase {

	use DriverSkipTrait;
	use IntegrationTestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Setup.Sessions',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Setup']);
	}

	/**
	 * @return void
	 */
	public function testPhpinfo() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'phpinfo']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testSession() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'session']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testCookies() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'cookies']);

		$this->assertResponseCode(200);
	}

	/**
	 * CakePHP's CspMiddleware stores the nonce as `cspScriptNonce`.
	 *
	 * @return void
	 */
	public function testCookiesUsesCspScriptNonce(): void {
		$this->disableErrorHandlerMiddleware();
		$this->withRequestAttribute('cspScriptNonce', 'core-nonce');

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'cookies']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('<script nonce="core-nonce">');
		$this->assertResponseNotRegExp('/<script(?![^>]*nonce=)/');
	}

	/**
	 * A custom middleware may still provide the nonce as `cspNonce`.
	 *
	 * @return void
	 */
	public function testCookiesFallsBackToCspNonce(): void {
		$this->disableErrorHandlerMiddleware();
		$this->withRequestAttribute('cspNonce', 'custom-nonce');

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'cookies']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('<script nonce="custom-nonce">');
	}

	/**
	 * @return void
	 */
	public function testCache() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'cache']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testTimezones() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'timezones']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testDatabase() {
		$this->skipIfNotDriver('Mysql', 'Only for Sqlite for now');

		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'database']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testEnv() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);
		Configure::write('Icon', [
			'sets' => [
				'bs' => BootstrapIcon::class,
			],
		]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'env']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testIp() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'ip']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testTypeMap() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'typeMap']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testCacheBenchmarkGet() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'cacheBenchmark']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testCacheBenchmarkPost() {
		$this->disableErrorHandlerMiddleware();

		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'cacheBenchmark'], []);

		$this->assertResponseCode(200);
		$this->assertResponseContains('File');
	}

	/**
	 * Adds a middleware that sets a request attribute, like a CSP middleware would.
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return void
	 */
	protected function withRequestAttribute(string $name, string $value): void {
		EventManager::instance()->on('Server.buildMiddleware', function (EventInterface $event, MiddlewareQueue $queue) use ($name, $value): void {
			$queue->prepend(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($name, $value): ResponseInterface {
				return $handler->handle($request->withAttribute($name, $value));
			});
		});
	}

}
