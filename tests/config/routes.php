<?php

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::defaultRouteClass(DashedRoute::class);

Router::scope('/', function (RouteBuilder $routes) {
	$routes->fallbacks();
});
Router::prefix('Admin', function (RouteBuilder $routes) {
	$routes->fallbacks();
});
