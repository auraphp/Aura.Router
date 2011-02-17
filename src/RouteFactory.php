<?php
/**
 * 
 * This file is part of the Aura framework for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace aura\router;

/**
 * 
 * A factory to create Route objects.
 * 
 * @package aura.router
 * 
 */
class RouteFactory
{
    /**
     * 
     * The list of Route constructor parameters with default values.
     * 
     * @var array
     * 
     */
    protected $ctor = array(
        'name'     => null,
        'path'     => null,
        'params'   => null,
        'values'   => null,
        'method'   => null,
        'secure'   => null,
        'is_match' => null,
        'get_path' => null,
        'name_prefix' => null,
        'path_prefix' => null,
    );
    
    /**
     * 
     * Returns a new Route instance.
     * 
     * @param array $spec An array of key-value pairs corresponding to the
     * Route constructor parameters.
     * 
     * @return Route
     * 
     */
    public function newInstance(array $spec)
    {
        $spec = array_merge($this->ctor, $spec);
        return new Route(
            $spec['name'],
            $spec['path'],
            $spec['params'],
            $spec['values'],
            $spec['method'],
            $spec['secure'],
            $spec['is_match'],
            $spec['get_path'],
            $spec['name_prefix'],
            $spec['path_prefix']
        );
    }
}
