<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::prefix('admin', function (RouteBuilder $routes) {
	$routes->plugin('Setup', ['path' => '/setup'], function (RouteBuilder $routes) {
		$routes->connect('/', ['controller' => 'Setup', 'action' => 'index']);

		$routes->fallbacks();
	});
});
