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
class Map extends AbstractSpec implements IteratorAggregate
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
     * A factory to create route objects.
     *
     * @var RouteFactory
     *
     */
    protected $routeFactory;

    /**
     *
     * A prefix to add to each route name added to the collection.
     *
     * @var string
     *
     */
    protected $namePrefix = null;

    /**
     *
     * A prefix to add to each route path added to the collection.
     *
     * @var string
     *
     */
    protected $pathPrefix = null;

    /**
     *
     * Constructor.
     *
     * @param RouteFactory $routeFactory A factory to create route objects.
     *
     */
    public function __construct(RouteFactory $routeFactory)
    {
        $this->routeFactory = $routeFactory;
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
            throw new Exception\RouteAlreadySet($name);
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
     * @param string $path The route path.
     *
     * @param string $name The route name.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function route($path, $name, array $defaults = [])
    {
        $path = $this->pathPrefix . $path;
        $name = $this->namePrefix . $name;
        $route = $this->routeFactory->newInstance($path, $name, $this->getSpec());
        $route->addDefaults($defaults);
        $this->addRoute($route);
        return $route;
    }

    /**
     *
     * Adds a GET route.
     *
     * @param string $path The route path.
     *
     * @param string $name The route name.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function get($path, $name, array $defaults = [])
    {
        $route = $this->route($path, $name, $defaults);
        $route->addMethod('GET');
        return $route;
    }

    /**
     *
     * Adds a DELETE route.
     *
     * @param string $path The route path.
     *
     * @param string $name The route name.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function delete($path, $name, array $defaults = [])
    {
        $route = $this->route($path, $name, $defaults);
        $route->addMethod('DELETE');
        return $route;
    }

    /**
     *
     * Adds a HEAD route.
     *
     * @param string $path The route path.
     *
     * @param string $name The route name.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function head($path, $name, array $defaults = [])
    {
        $route = $this->route($path, $name, $defaults);
        $route->addMethod('HEAD');
        return $route;
    }

    /**
     *
     * Adds an OPTIONS route.
     *
     * @param string $path The route path.
     *
     * @param string $name The route name.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function options($path, $name, array $defaults = [])
    {
        $route = $this->route($path, $name, $defaults);
        $route->addMethod('OPTIONS');
        return $route;
    }

    /**
     *
     * Adds a PATCH route.
     *
     * @param string $path The route path.
     *
     * @param string $name The route name.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function patch($path, $name, array $defaults = [])
    {
        $route = $this->route($path, $name, $defaults);
        $route->addMethod('PATCH');
        return $route;
    }

    /**
     *
     * Adds a POST route.
     *
     * @param string $path The route path.
     *
     * @param string $name The route name.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function post($path, $name, array $defaults = [])
    {
        $route = $this->route($path, $name, $defaults);
        $route->addMethod('POST');
        return $route;
    }

    /**
     *
     * Adds a PUT route.
     *
     * @param string $path The route path.
     *
     * @param string $name The route name.
     *
     * @param array $defaults An array of default attributes for the route.
     *
     * @return Route The newly-added route object.
     *
     */
    public function put($path, $name, array $defaults = [])
    {
        $route = $this->route($path, $name, $defaults);
        $route->addMethod('PUT');
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
    public function attach($pathPrefix, $namePrefix, $callable)
    {
        // retain current spec
        $spec = $this->getSpec();

        // add to existing prefixes, then run the callable
        $this->namePrefix .= $namePrefix;
        $this->pathPrefix .= $pathPrefix;
        $callable($this);

        // restore previous spec
        $this->setSpec($spec);
    }

    /**
     *
     * Gets the default route specification.
     *
     * @return array
     *
     */
    protected function getSpec()
    {
        $vars = array(
            'tokens',
            'server',
            'method',
            'accept',
            'defaults',
            'secure',
            'wildcard',
            'routable',
            'namePrefix',
            'pathPrefix',
        );

        $spec = array();
        foreach ($vars as $var) {
            $spec[$var] = $this->$var;
        }

        return $spec;
    }

    /**
     *
     * Sets the default route specification.
     *
     * @param array $spec The default route specification.
     *
     * @return null
     *
     */
    protected function setSpec($spec)
    {
        foreach ($spec as $key => $val) {
            $this->$key = $val;
        }
    }
}
