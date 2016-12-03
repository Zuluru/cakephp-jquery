<?php
use Cake\Routing\Router;

Router::plugin(
    'Jquery',
    ['path' => '/jquery'],
    function ($routes) {
        $routes->fallbacks('DashedRoute');
    }
);
