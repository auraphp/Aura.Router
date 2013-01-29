<?php
/**
 * Loader
 */
$loader->add('Aura\Router\\', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src');

/**
 * Services
 */
$di->set('router_map', $di->lazyNew('Aura\Router\Map'));

/**
 * Aura\Router\Map
 */
$di->params['Aura\Router\Map'] = [
    'definition_factory' => $di->lazyNew('Aura\Router\DefinitionFactory'),
    'route_factory' => $di->lazyNew('Aura\Router\RouteFactory'),
];
