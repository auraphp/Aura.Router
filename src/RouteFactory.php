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
    protected $args = array(
        'name'        => null,
        'path'        => null,
        'require'     => array(),
        'default'     => array(),
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
     * @param array $args An array of key-value pairs corresponding to the
     * Route arguments.
     * 
     * @return Route
     * 
     */
    public function newRoute(array $args)
    {
        $args = array_merge($this->args, $args);
        
        // set the name, with prefix if needed
	    $args['name_prefix'] = (string) $args['name_prefix'];
	    if ($args['name_prefix'] && $args['name']) {
	        $args['name'] = (string) $args['name_prefix'] . $args['name'];
	    } else {
	        $args['name'] = (string) $args['name'];
	    }
	    
	    // set the path, with prefix if needed
        $args['path_prefix'] = (string) $args['path_prefix'];
        if ($args['path_prefix'] && strpos($args['path'], '://') === false) {
            // concat the prefix and path
            $args['path'] = (string) $args['path_prefix'] . $args['path'];
            // convert all // to /, so that prefixes ending with / do not mess
            // with paths starting with /
            $args['path'] = str_replace('//', '/', $args['path']);
        } else {
            // no path prefix, or path has :// in it
            $args['path'] = (string) $args['path'];
        }

        // create and configure the route
        $route = new Route($args['path']);
        $route->setName($args['name'])
              ->setRequire((array) $args['require'])
              ->setDefault((array) $args['default'])
              ->setSecure($args['secure'])
              ->setWildcard($args['wildcard'])
              ->setRoutable($args['routable'])
              ->setIsMatchCallable($args['is_match'])
              ->setGenerateCallable($args['generate']);
        return $route;
    }
}
