<?php

namespace Setup\Middleware;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\ServerRequestFactory;
use Cake\Utility\Inflector;
use Cake\View\View;
use Cake\View\ViewBuilder;
use Laminas\Diactoros\CallbackStream;
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

		$builder->setClassName($this->getConfig('className'))
			->setTemplatePath(Inflector::camelize($templatePath));
		if (!$this->getConfig('templateLayout')) {
			$builder->disableAutoLayout();
		} else {
			$builder->setLayout($this->getConfig('templateLayout'));
		}

		$view = $builder
			->build([], $cakeRequest)
			->setConfig('_ext', $this->getConfig('templateExtension'));

		$bodyString = $view->render($templateName, $this->getConfig('templateLayout'));

		$response = $response->withHeader('Retry-After', (string)HOUR)
			->withHeader('Content-Type', $this->getConfig('contentType'))
			->withStatus($this->getConfig('statusCode'));

		$body = new CallbackStream(function () use ($bodyString) {
			return $bodyString;
		});

		/** @var \Psr\Http\Message\ResponseInterface $maintenanceResponse */
		$maintenanceResponse = $response->withBody($body);

		return $maintenanceResponse;
	}

}
