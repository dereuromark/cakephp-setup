<?php
use Cake\Core\Plugin;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::scope('/', function (RouteBuilder $routes) {
	$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => DashedRoute::class]);
	$routes->connect('/:controller/:action/*', [], ['routeClass' => DashedRoute::class]);
});

Router::prefix('admin', function (RouteBuilder $routes) {
	$routes->connect('/', ['controller' => 'Overview', 'action' => 'index']);

	$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => DashedRoute::class]);
	$routes->connect('/:controller/:action/*', [], ['routeClass' => DashedRoute::class]);
});

Plugin::routes();
