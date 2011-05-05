<?php
/**
 * Dependency services.
 */
$di->params['aura\router\Map']['route_factory'] = $di->lazyNew('aura\router\RouteFactory');

$di->set('router_map', function() use ($di) {
    return $di->newInstance('aura\router\Map');
});
