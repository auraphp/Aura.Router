<?php
/**
 * Loader
 */
$loader->add('Aura\Router\\', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src');

/**
 * Aura\Router\Router
 */
$di->params['Aura\Router\Router'] = array(
    'route_factory' => $di->lazyNew('Aura\Router\RouteFactory'),
);
