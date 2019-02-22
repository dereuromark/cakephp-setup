<?php

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::defaultRouteClass(DashedRoute::class);

Router::scope('/', function (RouteBuilder $routes) {
	$routes->connect('/:controller', ['action' => 'index']);
	$routes->connect('/:controller/:action/*');
});
Router::prefix('admin', function (RouteBuilder $routes) {
	$routes->connect('/', ['controller' => 'Overview', 'action' => 'index']);
	$routes->connect('/:controller', ['action' => 'index']);
	$routes->connect('/:controller/:action/*', []);
});

include ROOT . '/config/routes.php';
