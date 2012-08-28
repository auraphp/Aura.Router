<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @package Aura.Router
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Router;

/**
 * 
 * A factory to create Route objects.
 * 
 * @package Aura.Router
 * 
 */
class RouteFactory
{
    /**
     * 
     * An array of default parameters for Route objects.
     * 
     * @var array
     * 
     */
    protected $params = [
        'name'        => null,
        'path'        => null,
        'params'      => null,
        'values'      => null,
        'method'      => null,
        'secure'      => null,
        'routable'    => true,
        'is_match'    => null,
        'generate'    => null,
        'name_prefix' => null,
        'path_prefix' => null,
    ];

    /**
     * 
     * Returns a new Route instance.
     * 
     * @param array $params An array of key-value pairs corresponding to the
     * Route parameters.
     * 
     * @return Route
     * 
     */
    public function newInstance(array $params)
    {
        $params = array_merge($this->params, $params);
        return new Route(
            $params['name'],
            $params['path'],
            $params['params'],
            $params['values'],
            $params['method'],
            $params['secure'],
            $params['routable'],
            $params['is_match'],
            $params['generate'],
            $params['name_prefix'],
            $params['path_prefix']
        );
    }
}
