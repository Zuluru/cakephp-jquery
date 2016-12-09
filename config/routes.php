<?php
use Cake\Routing\Router;

Router::plugin(
    'ZuluruJquery',
    ['path' => '/zulurujquery'],
    function ($routes) {
        $routes->fallbacks('DashedRoute');
    }
);
