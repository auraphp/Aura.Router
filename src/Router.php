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

use Aura\Router\Exception;

/**
 * 
 * A wrapper for the collection of routes to be matched.
 * 
 * @package Aura.Router
 * 
 */
class Router
{
    /**
     * 
     * Logging information about which routes were attempted to match.
     * 
     * @var array
     * 
     */
    protected $debug = array();

    /**
     * 
     * Route objects created from the definitions.
     * 
     * @var RouteCollection
     * 
     */
    protected $routes;

    /**
     *
     * The Route object matched by the router.
     *
     * @var Route
     *
     */
    protected $matched_route = null;

    protected $generator;

	/**
	 *
	 * Constructor.
	 *
	 * @param RouteCollection $routes A route collection object.
	 */
    public function __construct(RouteCollection $routes, Generator $generator)
    {
        $this->routes = $routes;
        $this->generator = $generator;
    }
    
    /**
     * 
     * Makes the Router object a proxy for the RouteCollection.
     * 
     * @param string $func The method to call on the RouteCollection.
     * 
     * @param array $args The parameters for the call.
     * 
     * @return mixed
     * 
     */
    public function __call($func, $args)
    {
        return call_user_func_array(array($this->routes, $func), $args);
    }
    
    /**
     * 
     * Gets a route that matches a given path and other server conditions.
     * 
     * @param string $path The path to match against.
     * 
     * @param array $server A copy of the $_SERVER superglobal.
     * 
     * @return Route|false Returns a route object when it finds a match, or 
     * boolean false if there is no match.
     * 
     */
    public function match($path, array $server = array())
    {
        $this->debug = array();

        foreach ($this->routes as $route) {
            $match = $route->isMatch($path, $server);
            $this->debug[] = $route;
            if ($match) {
                $this->matched_route = $route;
                return $route;
            }
        }

        $this->matched_route = false;
        return false;
    }

    /**
     *
     * Returns the result of the call to match() again so you don't need to
     * run the matching process again.
     *
     * @return Route|false|null Returns null if match() has not been called
     * yet, false if it has and there was no match, or a Route object if there
     * was a match.
     *
     */
    public function getMatchedRoute()
    {
        return $this->matched_route;
    }

    /**
     *
     * Looks up a route by name, and interpolates data into it to return
     * a URI path.
     * 
     * @param string $name The route name to look up.
     * 
     * @param array $data The data to interpolate into the URI; data keys
     * map to param tokens in the path.
     * 
     * @return string|false A URI path string if the route name is found, or
     * boolean false if not.
     * 
     */
    public function generate($name, $data = null)
    {
        if (! $this->routes->offsetExists($name)) {
            throw new Exception\RouteNotFound($name);
        }
        
        $route = $this->routes->offsetGet($name);
        return $this->generator->generate($route, $data);
    }
    
    /**
     * 
     * Sets the array of route objects to use.
     * 
     * @param RouteCollection $routes Use this RouteCollection object.
     * 
     * @return null
     * 
     * @see getRoutes()
     * 
     */
    public function setRoutes(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * 
     * Gets the route collection.
     * 
     * @return RouteCollection
     * 
     * @see setRoutes()
     * 
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * 
     * Gets the attempted route matches.
     * 
     * @return array An array of routes from the last match() attempt.
     * 
     */
    public function getDebug()
    {
        return $this->debug;
    }
}
