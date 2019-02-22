<?php

namespace App;

use Cake\Http\BaseApplication;
use Cake\Routing\Middleware\RoutingMiddleware;

class Application extends BaseApplication {

	/**
	 * @inheritDoc
	 */
	public function bootstrap() {
		// Call parent to load bootstrap from files.
		parent::bootstrap();

		$this->addPlugin('Tools');
	}

	/**
	 * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to set in your App Class
	 * @return \Cake\Http\MiddlewareQueue
	 */
	public function middleware($middleware) {
		$middleware->add(new RoutingMiddleware($this));

		return $middleware;
	}

}
