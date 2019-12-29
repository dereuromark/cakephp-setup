<?php

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin('<%= $plugin %>', function (RouteBuilder $routes) {
    $routes->fallbacks();
});
