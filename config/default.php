<?php
/**
 * Aura\Router\Router
 */
$di->params['Aura\Router\Router'] = array(
    'route_factory' => $di->lazyNew('Aura\Router\RouteFactory'),
);
