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
     * The class to create.
     * 
     * @param string
     * 
     */
    protected $class = 'Aura\Router\Route';
    
    /**
	 * 
	 * A default specification array.
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
	 * Constructor.
	 * 
	 * @param string $class The class to create.
	 * 
	 */
	public function __construct($class = 'Aura\Router\Route')
	{
	    $this->class = $class;
	}
	
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
        $class = $this->class;
        $route = new $class($spec['name'], $spec['path']);
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
