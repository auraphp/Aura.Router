<?php
/**
 * Aura\Router\RouteCollection
 */
$di->params['Aura\Router\RouteCollection'] = array(
    'route_factory' => $di->lazyNew('Aura\Router\RouteFactory'),
);

/**
 * Aura\Router\Router
 */
$di->params['Aura\Router\Router'] = array(
    'routes' => $di->lazyNew('Aura\Router\RouteCollection'),
);
