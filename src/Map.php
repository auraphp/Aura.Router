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
use Countable;
use IteratorAggregate;

/**
 *
 * A collection of route objects.
 *
 * @package Aura.Router
 *
 */
class Map extends AbstractSpec implements Countable, IteratorAggregate
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
     * Countable: returns the number of routes in the collection.
     *
     * @return int
     *
     */
    public function count()
    {
        return count($this->routes);
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
     * Adds a route.
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
        // create the route with the full path, name, and spec
        $route = $this->routeFactory->newInstance(
            $path,
            $name,
            $this->getSpec()
        );

        $route->addDefaults($defaults);

        // add the route
        if (! $route->name) {
            $this->routes[] = $route;
        } else {
            $this->routes[$route->name] = $route;
        }

        // done
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
        $route->addMethod('GET');
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
        $route->addMethod('DELETE');
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
        $route->addMethod('HEAD');
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
        $route->addMethod('OPTIONS');
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
        $route->addMethod('PATCH');
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
        $route->addMethod('POST');
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
        $route->addMethod('PUT');
        return $route;
    }

    /**
     *
     * Attaches routes to a specific path prefix, and prefixes the attached
     * route names.
     *
     * @param string $name The prefix for all route names being attached.
     *
     * @param string $path The prefix for all route paths being attached.
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
