<?php
/**
 * Package prefix for autoloader.
 */
$loader->add('Aura\Router\\', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src');

/**
 * Instance params and setter values.
 */
$di->params['Aura\Router\Map']['definition_factory'] = $di->lazyNew('Aura\Router\DefinitionFactory');
$di->params['Aura\Router\Map']['route_factory'] = $di->lazyNew('Aura\Router\RouteFactory');

/**
 * Dependency services.
 */
$di->set('router_map', $di->lazyNew('Aura\Router\Map'));
