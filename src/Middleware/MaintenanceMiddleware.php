<?php
namespace Setup\Middleware;

use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Inflector;
use Cake\View\View;
use Cake\View\ViewBuilder;
use Cake\Network\Request;
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
		$this->config($config);
	}

	/**
	 * @param \Cake\Http\ServerRequest $request The request.
	 * @param \Cake\Network\Response $response The response.
	 * @param callable $next The next middleware to call.
	 * @return \Psr\Http\Message\ResponseInterface A response.
	 */
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
	{
		$ip = $request->clientIp();
		$Maintenance = new Maintenance();
		if (!$Maintenance->isMaintenanceMode($ip)) {
			return $next($request, $response);
		}

		$response = $this->build($response);

		return $response;
	}

	/**
	 * @param \Cake\Network\Response $response The response.
	 * @return \Cake\Network\Response
	 */
	protected function build($response) {
		$cakeRequest = Request::createFromGlobals();
		$builder = new ViewBuilder();

		$templateName = $this->config('templateFileName');
		$templatePath = $this->config('templatePath');

		$view = $builder
			->className($this->config('className'))
			->templatePath(Inflector::camelize($templatePath))
			->layout($this->config('templateLayout'))
			->build([], $cakeRequest);
		$view->_ext = $this->config('templateExtension');

		$bodyString = $view->render($templateName);

		$response = $response->withHeader('Retry-After', (string)HOUR)
			->withHeader('Content-Type', $this->config('contentType'))
			->withStatus($this->config('statusCode'));

		$body = $response->getBody();
		$body->write($bodyString);

		return $response;
	}

}
