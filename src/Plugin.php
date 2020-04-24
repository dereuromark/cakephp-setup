<?php

namespace Setup;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;

/**
 * Plugin for Setup
 */
class Plugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected $middlewareEnabled = false;

	/**
	 * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
	 * @return void
	 */
	public function routes(RouteBuilder $routes): void {
		$routes->prefix('Admin', function (RouteBuilder $routes) {
			$routes->plugin('Setup', function (RouteBuilder $routes) {
				$routes->connect('/', ['controller' => 'Setup', 'action' => 'index']);

				$routes->fallbacks();
			});
		});
	}

}
