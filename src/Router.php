<?php
/**
 *
 * This file is part of the Aura for PHP.
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
 * @method Route add() add($name, $path, $action = null) Adds a route
 * @method Route addGet() addGet($name, $path, $action = null)  Adds a GET route
 * @method Route addDelete() addDelete($name, $path, $action = null) Adds a DELETE route
 * @method Route addHead() addHead($name, $path, $action = null)  Adds a HEAD route
 * @method Route addOptions() addOptions($name, $path, $action = null)  Adds a OPTIONS route
 * @method Route addPatch() addPatch($name, $path, $action = null)  Adds a PATCH route
 * @method Route addPost() addPost($name, $path, $action = null)  Adds a POST route
 * @method Route addPut() addPut($name, $path, $action = null)  Adds a PUT route
 * @method Route setRouteCallable() setRouteCallable($callable) Sets the callable for modifying a newly-added route before it is returned.
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
     * @var Route|false
     *
     */
    protected $matched_route = null;

    /**
     *
     * A URL path generator.
     *
     * @var Generator
     *
     */
    protected $generator;

    /**
     *
     * The first of the closest-matching failed routes.
     *
     * @var Route
     *
     */
    protected $failed_route = null;

    /**
     *
     * A basepath to all routes.
     *
     * @var string
     *
     */
    protected $basepath;

    /**
     *
     * Constructor.
     *
     * @param RouteCollection $routes A route collection object.
     *
     * @param Generator $generator A URL path generator.
     *
     * @param string $basepath A basepath to to all routes.
     *
     */
    public function __construct(
        RouteCollection $routes,
        Generator $generator,
        $basepath = null
    ) {
        $this->routes = $routes;
        $this->generator = $generator;
        $this->basepath = rtrim($basepath, '/');
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
        $this->failed_route = null;

        foreach ($this->routes as $route) {

            $this->debug[] = $route;

            $match = $route->isMatch($path, $server, $this->basepath);
            if ($match) {
                $this->matched_route = $route;
                return $route;
            }

            $better_match = ! $this->failed_route
                         || $route->score > $this->failed_route->score;
            if ($better_match) {
                $this->failed_route = $route;
            }
        }

        $this->matched_route = false;
        return false;
    }

    /**
     *
     * Get the first of the closest-matching failed routes.
     *
     * @return Route
     *
     */
    public function getFailedRoute()
    {
        return $this->failed_route;
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
     * @throws Exception\RouteNotFound
     *
     */
    public function generate($name, $data = array())
    {
        $route = $this->getRouteForGenerate($name);
        return $this->basepath . $this->generator->generate($route, $data);
    }

    /**
     *
     * Generate the route without url encoding.
     *
     * @param string $name The route name to look up.
     *
     * @param array $data The data to interpolate into the URI; data keys
     * map to param tokens in the path.
     *
     * @return string|false A URI path string if the route name is found, or
     * boolean false if not.
     *
     * @throws Exception\RouteNotFound
     *
     */
    public function generateRaw($name, $data = array())
    {
        $route = $this->getRouteForGenerate($name);
        return $this->basepath . $this->generator->generateRaw($route, $data);
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

    /**
     *
     * Gets a Route for generation.
     *
     * @param string $name Get this route name.
     *
     * @return Route
     *
     * @throws Exception\RouteNotFound when the named route does not exist.
     *
     */
    protected function getRouteForGenerate($name)
    {
        if (! $this->routes->offsetExists($name)) {
            throw new Exception\RouteNotFound($name);
        }

        return $this->routes->offsetGet($name);
    }
}
