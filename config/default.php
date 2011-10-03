<?php
/**
 * Package prefix for autoloader.
 */
$loader->addPrefix('Aura\Router\\', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src');

/**
 * Dependency services.
 */
$di->params['Aura\Router\Map']['route_factory'] = $di->lazyNew('Aura\Router\RouteFactory');

$di->set('router_map', function() use ($di) {
    return $di->newInstance('Aura\Router\Map');
});
