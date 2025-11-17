<?php

namespace Setup\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Shim\TestSuite\TestCase;

/**
 * @uses \Setup\Controller\HealthcheckController
 */
class HealthcheckControllerTest extends TestCase {

	use IntegrationTestTrait;

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
	public function testIndex() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'Setup', 'controller' => 'Healthcheck', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testIndexJson() {
		$this->disableErrorHandlerMiddleware();
		Configure::write('debug', true);

		$this->configRequest([
			'headers' => ['Accept' => 'application/json'],
		]);
		$this->get(['plugin' => 'Setup', 'controller' => 'Healthcheck', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertContentType('application/json');

		$response = json_decode((string)$this->_response->getBody(), true);
		$this->assertNotEmpty($response);
		$this->assertArrayHasKey('passed', $response);
		$this->assertArrayHasKey('metadata', $response);
		$this->assertArrayHasKey('result', $response);

		// Check metadata structure
		$metadata = $response['metadata'];
		$this->assertArrayHasKey('timestamp', $metadata);
		$this->assertArrayHasKey('execution_time_ms', $metadata);
		$this->assertArrayHasKey('total_checks', $metadata);
		$this->assertArrayHasKey('errors', $metadata);
		$this->assertArrayHasKey('warnings', $metadata);
		$this->assertArrayHasKey('domain', $metadata);
		$this->assertSame('all', $metadata['domain']);
		$this->assertIsNumeric($metadata['execution_time_ms']);
		$this->assertIsInt($metadata['total_checks']);
		$this->assertIsInt($metadata['errors']);
		$this->assertIsInt($metadata['warnings']);
	}

	/**
	 * @return void
	 */
	public function testIndexJsonWithDomainFilter() {
		$this->disableErrorHandlerMiddleware();
		Configure::write('debug', true);

		$this->configRequest([
			'headers' => ['Accept' => 'application/json'],
		]);
		$this->get(['plugin' => 'Setup', 'controller' => 'Healthcheck', 'action' => 'index', '?' => ['domain' => 'Core']]);

		$this->assertResponseCode(200);
		$this->assertContentType('application/json');

		$response = json_decode((string)$this->_response->getBody(), true);
		$this->assertNotEmpty($response);
		$this->assertArrayHasKey('metadata', $response);
		$this->assertSame('Core', $response['metadata']['domain']);
	}

	/**
	 * @return void
	 */
	public function testIndexJsonDebugOff() {
		$this->disableErrorHandlerMiddleware();
		Configure::write('debug', false);

		$this->configRequest([
			'headers' => ['Accept' => 'application/json'],
		]);
		$this->get(['plugin' => 'Setup', 'controller' => 'Healthcheck', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertContentType('application/json');

		$response = json_decode((string)$this->_response->getBody(), true);
		$this->assertNotEmpty($response);
		$this->assertArrayHasKey('passed', $response);
		$this->assertArrayHasKey('metadata', $response);
		$this->assertArrayNotHasKey('result', $response); // Should not include result when debug is off
	}

	/**
	 * @return void
	 */
	public function testIndexJsonResultFormat() {
		$this->disableErrorHandlerMiddleware();
		Configure::write('debug', true);

		$this->configRequest([
			'headers' => ['Accept' => 'application/json'],
		]);
		$this->get(['plugin' => 'Setup', 'controller' => 'Healthcheck', 'action' => 'index']);

		$this->assertResponseCode(200);

		$response = json_decode((string)$this->_response->getBody(), true);
		$this->assertArrayHasKey('result', $response);

		// Check that result has proper structure
		foreach ($response['result'] as $domain => $checks) {
			$this->assertIsString($domain);
			$this->assertIsArray($checks);
			foreach ($checks as $check) {
				$this->assertArrayHasKey('name', $check);
				$this->assertArrayHasKey('passed', $check);
				$this->assertArrayHasKey('level', $check);
				$this->assertArrayHasKey('priority', $check);
				$this->assertArrayHasKey('messages', $check);

				$messages = $check['messages'];
				$this->assertArrayHasKey('success', $messages);
				$this->assertArrayHasKey('warning', $messages);
				$this->assertArrayHasKey('failure', $messages);
				$this->assertArrayHasKey('info', $messages);
			}
		}
	}

	/**
	 * @return void
	 */
	public function testIndexWithJsonExtension() {
		$this->disableErrorHandlerMiddleware();
		Configure::write('debug', true);

		$this->get(['plugin' => 'Setup', 'controller' => 'Healthcheck', 'action' => 'index', '_ext' => 'json']);

		$this->assertResponseCode(200);
		$this->assertContentType('application/json');

		$response = json_decode((string)$this->_response->getBody(), true);
		$this->assertNotEmpty($response);
		$this->assertArrayHasKey('passed', $response);
		$this->assertArrayHasKey('metadata', $response);
		$this->assertArrayHasKey('result', $response);

		// Verify it has the same structure as regular JSON request
		$metadata = $response['metadata'];
		$this->assertArrayHasKey('timestamp', $metadata);
		$this->assertArrayHasKey('execution_time_ms', $metadata);
		$this->assertArrayHasKey('total_checks', $metadata);
		$this->assertArrayHasKey('errors', $metadata);
		$this->assertArrayHasKey('warnings', $metadata);
		$this->assertArrayHasKey('domain', $metadata);
	}

	/**
	 * @return void
	 */
	public function testIndexWithJsonExtensionAndDomain() {
		$this->disableErrorHandlerMiddleware();
		Configure::write('debug', true);

		$this->get(['plugin' => 'Setup', 'controller' => 'Healthcheck', 'action' => 'index', '_ext' => 'json', '?' => ['domain' => 'Database']]);

		$this->assertResponseCode(200);
		$this->assertContentType('application/json');

		$response = json_decode((string)$this->_response->getBody(), true);
		$this->assertNotEmpty($response);
		$this->assertArrayHasKey('metadata', $response);
		$this->assertSame('Database', $response['metadata']['domain']);
	}

	/**
	 * @return void
	 */
	public function testIndexWithJsonExtensionDebugOff() {
		$this->disableErrorHandlerMiddleware();
		Configure::write('debug', false);

		$this->get(['plugin' => 'Setup', 'controller' => 'Healthcheck', 'action' => 'index', '_ext' => 'json']);

		$this->assertResponseCode(200);
		$this->assertContentType('application/json');

		$response = json_decode((string)$this->_response->getBody(), true);
		$this->assertNotEmpty($response);
		$this->assertArrayHasKey('passed', $response);
		$this->assertArrayHasKey('metadata', $response);
		$this->assertArrayNotHasKey('result', $response); // Should not include result when debug is off
	}

}
