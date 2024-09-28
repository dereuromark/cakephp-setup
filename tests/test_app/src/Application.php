<?php

namespace TestApp;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;

class Application extends BaseApplication {

	/**
	 * @inheritDoc
	 */
	public function bootstrap(): void {
		// Call parent to load bootstrap from files.
		parent::bootstrap();

		$this->addPlugin('Tools');
		$this->addPlugin('Templating');
	}

	/**
	 * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to set in your App Class
	 *
	 * @return \Cake\Http\MiddlewareQueue
	 */
	public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue {
		$middlewareQueue->add(new RoutingMiddleware($this));

		return $middlewareQueue;
	}

}
