<?php
/**
 * Autoloader info.
 */
$loader->setPath('aura\router\\', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src');

/**
 * Dependency services.
 */
$di->set('router_map', function() use ($di) {
    $map = new aura\router\Map(new aura\router\RouteFactory);
    $map->attach(null, array(
        'routes' => array(
            '/{:controller}/{:action}/{:id}{:format:(\..+)?}',
            '/{:controller}/{:action}/{:id}',
            '/{:controller}/{:action}',
            '/{:controller}',
            '/',
        ),
    ));
});
