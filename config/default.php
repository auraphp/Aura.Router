<?php
/**
 * Loader
 */
$loader->add('Aura\Router\\', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src');

/**
 * Aura\Router\Map
 */
$di->params['Aura\Router\Map'] = [
    'definition_factory' => $di->lazyNew('Aura\Router\DefinitionFactory'),
    'route_factory' => $di->lazyNew('Aura\Router\RouteFactory'),
];
