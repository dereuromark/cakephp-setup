<?php

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {

	$routes->setRouteClass(DashedRoute::class);

	$routes->scope('/', function (RouteBuilder $routes) {
		$routes->fallbacks();
	});
	$routes->prefix('Admin', function (RouteBuilder $routes) {
		$routes->fallbacks();
	});
};
