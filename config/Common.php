<?php
namespace Aura\Router\_Config;

use Aura\Di\Config;
use Aura\Di\Container;

class Common extends Config
{
    public function define(Container $di)
    {
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
            'generator' => $di->lazyNew('Aura\Router\Generator'),
        );
    }
}
