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
 * A factory to create Route objects.
 * 
 * @package Aura.Router
 * 
 */
class RouteFactory
{
    /**
     * 
     * The route class to create.
     * 
     * @param string
     * 
     */
    protected $class = 'Aura\Router\Route';
    
	/**
	 * 
	 * Constructor.
	 * 
	 * @param string $class The route class to create.
	 * 
	 */
	public function __construct($class = 'Aura\Router\Route')
	{
	    $this->class = $class;
	}
	
    /**
     * 
     * Returns a new instance of the route class.
     * 
     * @param string $path The path for the route.
     * 
     * @param string $name The name for the route.
     * 
     * @return Route
     * 
     */
    public function newInstance($path, $name = null)
    {
        $class = $this->class;
        return new $class($path, $name);
    }
}
