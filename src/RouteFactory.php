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
	 * An array of default Route specifications.
	 * 
	 * @var array
	 * 
	 */
	protected $spec = array(
	    'name'        => null,
	    'path'        => null,
	    'require'     => array(),
	    'default'     => array(),
	    'secure'      => null,
	    'wildcard'    => null,
	    'routable'    => true,
	    'is_match'    => null,
	    'generate'    => null,
	);
	
    /**
     * 
     * Returns a new Route instance.
     * 
     * @param array $spec The Route specification.
     * 
     * @return Route
     * 
     */
    public function newInstance(array $spec = array())
    {
        $spec = array_merge($this->spec, $spec);
        $route = new Route($spec['name'], $spec['path']);
        $route->setRequire((array) $spec['require'])
              ->setDefault((array) $spec['default'])
              ->setSecure($spec['secure'])
              ->setWildcard($spec['wildcard'])
              ->setRoutable($spec['routable'])
              ->setIsMatchCallable($spec['is_match'])
              ->setGenerateCallable($spec['generate']);
        return $route;
    }
}
