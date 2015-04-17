<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

use ArrayIterator;
use IteratorAggregate;

/**
 *
 * A collection of route objects.
 *
 * @package Aura.Router
 *
 */
class Map implements IteratorAggregate
{
    /**
     *
     * An array of route objects.
     *
     * @var array
     *
     */
    protected $routes = array();

    /**
     *
     * A prototype Route.
     *
     * @var Route
     *
     */
    protected $protoRoute;

    /**
     *
     * Constructor.
     *
     * @param Route $protoRoute A prototype Route.
     *
     */
    public function __construct(Route $protoRoute)
    {
        $this->protoRoute = $protoRoute;
    }

    public function __call($method, $params)
    {
        call_user_func_array([$this->protoRoute, $method], $params);
        return $this;
    }

    /**
     *
     * IteratorAggregate: returns the iterator object.
     *
     * @return ArrayIterator
     *
     */
    public function getIterator()
    {
        return new ArrayIterator($this->routes);
    }

    /**
     *
     * Sets the array of route objects to use.
     *
     * @param array $routes Use this array of routes.
     *
     * @return null
     *
     * @see getRoutes()
     *
     */
    public function setRoutes(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     *
     * Gets the route collection.
     *
     * @return Map
     *
     * @see setRoutes()
     *
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    public function addRoute(Route $route)
    {
        $name = $route->name;

        if (! $name) {
            $this->routes[] = $route;
            return;
        }

        if (isset($this->routes[$name])) {
            throw new Exception\RouteAlreadyExists($name);
        }

        $this->routes[$name] = $route;
    }

    /**
     *
     * Gets a route by name.
     *
     * @param string $name The route name.
     *
     * @return Route
     *
     */
    public function getRoute($name)
    {
        if (! isset($this->routes[$name])) {
            throw new Exception\RouteNotFound($name);
        }

        return $this->routes[$name];
    }

    /**
     *
     * Adds a generic route.
     *
     * @param string $name The route name.
     *
     * @param string $path The route path.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function route($name, $path, array $defaults = [])
    {
        $route = clone $this->protoRoute;

        $route->setName($name);
        $route->setPath($path);
        $route->addDefaults($defaults);

        $this->addRoute($route);
        return $route;
    }

    /**
     *
     * Adds a GET route.
     *
     * @param string $name The route name.
     *
     * @param string $path The route path.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function get($name, $path, array $defaults = [])
    {
        $route = $this->route($name, $path, $defaults);
        $route->addMethods('GET');
        return $route;
    }

    /**
     *
     * Adds a DELETE route.
     *
     * @param string $name The route name.
     *
     * @param string $path The route path.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function delete($name, $path, array $defaults = [])
    {
        $route = $this->route($name, $path, $defaults);
        $route->addMethods('DELETE');
        return $route;
    }

    /**
     *
     * Adds a HEAD route.
     *
     * @param string $name The route name.
     *
     * @param string $path The route path.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function head($name, $path, array $defaults = [])
    {
        $route = $this->route($name, $path, $defaults);
        $route->addMethods('HEAD');
        return $route;
    }

    /**
     *
     * Adds an OPTIONS route.
     *
     * @param string $name The route name.
     *
     * @param string $path The route path.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function options($name, $path, array $defaults = [])
    {
        $route = $this->route($name, $path, $defaults);
        $route->addMethods('OPTIONS');
        return $route;
    }

    /**
     *
     * Adds a PATCH route.
     *
     * @param string $name The route name.
     *
     * @param string $path The route path.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function patch($name, $path, array $defaults = [])
    {
        $route = $this->route($name, $path, $defaults);
        $route->addMethods('PATCH');
        return $route;
    }

    /**
     *
     * Adds a POST route.
     *
     * @param string $name The route name.
     *
     * @param string $path The route path.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function post($name, $path, array $defaults = [])
    {
        $route = $this->route($name, $path, $defaults);
        $route->addMethods('POST');
        return $route;
    }

    /**
     *
     * Adds a PUT route.
     *
     * @param string $name The route name.
     *
     * @param string $path The route path.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function put($name, $path, array $defaults = [])
    {
        $route = $this->route($name, $path, $defaults);
        $route->addMethods('PUT');
        return $route;
    }

    /**
     *
     * Attaches routes to a specific path prefix, and prefixes the attached
     * route names.
     *
     * @param string $pathPrefix The prefix for all route paths being attached.
     *
     * @param string $namePrefix The prefix for all route names being attached.
     *
     * @param callable $callable A callable that uses the Router to add new
     * routes. Its signature is `function (\Aura\Router\Map $map)`; this
     * Map instance will be passed to the callable.
     *
     * @return null
     *
     */
    public function attach($namePrefix, $pathPrefix, $callable)
    {
        // retain current prototype and replace with a clone
        $previous = $this->protoRoute;

        // add to existing prefixes, then run the callable
        $this->protoRoute = clone $this->protoRoute;
        $this->protoRoute->appendNamePrefix($namePrefix);
        $this->protoRoute->appendPathPrefix($pathPrefix);
        $callable($this);

        // restore previous prototype
        $this->protoRoute = $previous;
    }
}
