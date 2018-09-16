<?php
namespace Setup\Middleware;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\ServerRequestFactory;
use Cake\Utility\Inflector;
use Cake\View\View;
use Cake\View\ViewBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Setup\Maintenance\Maintenance;

class MaintenanceMiddleware {

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
		'templateExtension' => '.ctp',
		'contentType' => 'text/html'
	];

	/**
	 * @param array $config
	 */
	public function __construct(array $config = []) {
		$this->setConfig($config);
	}

	/**
	 * @param \Cake\Http\ServerRequest $request The request.
	 * @param \Cake\Http\Response $response The response.
	 * @param callable $next The next middleware to call.
	 * @return \Psr\Http\Message\ResponseInterface A response.
	 */
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next) {
		$ip = $request->clientIp();
		$Maintenance = new Maintenance();
		if (!$Maintenance->isMaintenanceMode($ip)) {
			return $next($request, $response);
		}

		$response = $this->build($response);

		return $response;
	}

	/**
	 * @param \Cake\Http\Response $response The response.
	 * @return \Cake\Http\Response
	 */
	protected function build($response) {
		$cakeRequest = ServerRequestFactory::fromGlobals();
		$builder = new ViewBuilder();

		$templateName = $this->getConfig('templateFileName');
		$templatePath = $this->getConfig('templatePath');

		$view = $builder
			->className($this->getConfig('className'))
			->templatePath(Inflector::camelize($templatePath))
			->layout($this->getConfig('templateLayout'))
			->build([], $cakeRequest);
		$view->_ext = $this->getConfig('templateExtension');

		$bodyString = $view->render($templateName);

		$response = $response->withHeader('Retry-After', (string)HOUR)
			->withHeader('Content-Type', $this->getConfig('contentType'))
			->withStatus($this->getConfig('statusCode'));

		$body = $response->getBody();
		$body->write($bodyString);

		return $response;
	}

}
