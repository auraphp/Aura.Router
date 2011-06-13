<?php
/**
 * Dependency services.
 */
$di->params['Aura\Router\Map']['route_factory'] = $di->lazyNew('Aura\Router\RouteFactory');

$di->set('router_map', function() use ($di) {
    return $di->newInstance('Aura\Router\Map');
});
