<?php
/**
 * 
 * This file is part of the Aura for PHP.
 * 
 * @package Aura.Router
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Router;

/**
 * 
 * A factory to create Definition and Route objects.
 * 
 * @package Aura.Router
 * 
 */
class RouteFactory
{
    /**
     * 
     * Returns a new Definition instance.
     * 
     * @param string $type The type of definition, 'single' or 'attach'.
     * 
     * @param array|callable $spec The definition spec: either an array, or a
     * callable that returns an array.
     * 
     * @param string $path_prefix For 'attach' definitions, use this as the 
     * prefix for attached paths.
     * 
     * @return Route
     * 
     */
    public function newDefinition($type, $spec, $path_prefix = null)
    {
        return new Definition($type, $spec, $path_prefix);
    }
    
    /**
     * 
     * An array of default parameters for Route objects.
     * 
     * @var array
     * 
     */
    protected $params = array(
        'name'        => null,
        'path'        => null,
        'params'      => null,
        'values'      => null,
        'method'      => null,
        'secure'      => null,
        'wildcard'    => null,
        'routable'    => true,
        'is_match'    => null,
        'generate'    => null,
        'name_prefix' => null,
        'path_prefix' => null,
    );

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
    public function newRoute(array $params)
    {
        $params = array_merge($this->params, $params);
        return new Route(
            $params['name'],
            $params['path'],
            $params['params'],
            $params['values'],
            $params['method'],
            $params['secure'],
            $params['wildcard'],
            $params['routable'],
            $params['is_match'],
            $params['generate'],
            $params['name_prefix'],
            $params['path_prefix']
        );
    }
}
