<?php

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::defaultRouteClass(DashedRoute::class);

Router::scope('/', function (RouteBuilder $routes) {
	$routes->fallbacks();
});
Router::prefix('Admin', function (RouteBuilder $routes) {
	$routes->fallbacks();
});
