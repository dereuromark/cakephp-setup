<?php

namespace Setup\Middleware;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\ServerRequestFactory;
use Cake\Utility\Inflector;
use Cake\View\View;
use Cake\View\ViewBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Setup\Maintenance\Maintenance;

class MaintenanceMiddleware implements MiddlewareInterface {

	use InstanceConfigTrait;

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'className' => View::class,
		'templatePath' => 'Error',
		'statusCode' => 503,
		'templateLayout' => false,
		'templateFileName' => 'maintenance',
		'templateExtension' => '.php',
		'contentType' => 'text/html',
	];

	/**
	 * @param array $config
	 */
	public function __construct(array $config = []) {
		$this->setConfig($config);
	}

	/**
	 * @param \Cake\Http\ServerRequest $request
	 * @param \Psr\Http\Server\RequestHandlerInterface $handler
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$ip = $request->clientIp();
		$maintenance = new Maintenance();

		$response = $handler->handle($request);
		if (!$maintenance->isMaintenanceMode($ip)) {
			return $response;
		}

		$response = $this->build($response);

		return $response;
	}

	/**
	 * @param \Psr\Http\Message\ResponseInterface $response
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	protected function build(ResponseInterface $response) {
		$cakeRequest = ServerRequestFactory::fromGlobals();
		$builder = new ViewBuilder();

		$templateName = $this->getConfig('templateFileName');
		$templatePath = $this->getConfig('templatePath');

		$view = $builder
			->setClassName($this->getConfig('className'))
			->setTemplatePath(Inflector::camelize($templatePath))
			->setLayout($this->getConfig('templateLayout'))
			->build([], $cakeRequest)
			->setConfig('_ext', $this->getConfig('templateExtension'));
		//$view->_ext = $this->getConfig('templateExtension');

		$bodyString = $view->render($templateName);

		$response = $response->withHeader('Retry-After', (string)HOUR)
			->withHeader('Content-Type', $this->getConfig('contentType'))
			->withStatus($this->getConfig('statusCode'));

		$body = $response->getBody();
		$body->write($bodyString);

		return $response;
	}

}
