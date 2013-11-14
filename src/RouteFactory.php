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
    public function newInstance($path, $name = null)
    {
        $class = $this->class;
        return new $class($path, $name);
    }
}
