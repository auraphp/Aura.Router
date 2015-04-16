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
     * A callable to use for each resource attached to the collection.
     *
     * @var callable
     *
     * @see attachResource()
     *
     */
    protected $resourceCallable = null;

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
        $this->setResourceCallable(array($this, 'resourceCallable'));
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
     * @param mixed $action A value for $route->defaults['action'].
     *
     * @return Route The newly-added route object.
     *
     */
    public function add($name, $path, $action = null)
    {
        // create the route with the full path, name, and spec
        $route = $this->routeFactory->newInstance(
            $path,
            $name,
            $this->getSpec($action)
        );

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
     * @param mixed $action A value for $route->defaults['action'].
     *
     * @return Route The newly-added route object.
     *
     */
    public function addGet($name, $path, $action = null)
    {
        $route = $this->add($name, $path, $action);
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
     * @param mixed $action A value for $route->defaults['action'].
     *
     * @return Route The newly-added route object.
     *
     */
    public function addDelete($name, $path, $action = null)
    {
        $route = $this->add($name, $path, $action);
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
     * @param mixed $action A value for $route->defaults['action'].
     *
     * @return Route The newly-added route object.
     *
     */
    public function addHead($name, $path, $action = null)
    {
        $route = $this->add($name, $path, $action);
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
     * @param mixed $action A value for $route->defaults['action'].
     *
     * @return Route The newly-added route object.
     *
     */
    public function addOptions($name, $path, $action = null)
    {
        $route = $this->add($name, $path, $action);
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
     * @param mixed $action A value for $route->defaults['action'].
     *
     * @return Route The newly-added route object.
     *
     */
    public function addPatch($name, $path, $action = null)
    {
        $route = $this->add($name, $path, $action);
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
     * @param mixed $action A value for $route->defaults['action'].
     *
     * @return Route The newly-added route object.
     *
     */
    public function addPost($name, $path, $action = null)
    {
        $route = $this->add($name, $path, $action);
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
     * @param mixed $action A value for $route->defaults['action'].
     *
     * @return Route The newly-added route object.
     *
     */
    public function addPut($name, $path, $action = null)
    {
        $route = $this->add($name, $path, $action);
        $route->addMethod('PUT');
        return $route;
    }

    /**
     *
     * Attaches routes to a specific path prefix, and prefixes the attached
     * route names.
     *
     * @param string $name The prefix for all route names being
     * attached.
     *
     * @param string $path The prefix for all route paths being
     * attached.
     *
     * @param callable $callable A callable that uses the Router to add new
     * routes. Its signature is `function (\Aura\Router\Map $map)`; this
     * Map instance will be passed to the callable.
     *
     * @return null
     *
     */
    public function attach($name, $path, $callable)
    {
        // save current spec
        $spec = $this->getSpec();

        // append to the name prefix, with delimiter if needed
        if ($this->namePrefix) {
            $this->namePrefix .= '.';
        }
        $this->namePrefix .= $name;

        // append to the path prefix
        $this->pathPrefix .= $path;

        // invoke the callable, passing this Map as the only attribute
        call_user_func($callable, $this);

        // restore previous spec
        $this->setSpec($spec);
    }

    /**
     *
     * Gets the existing default route specification.
     *
     * @param mixed $action A value for $route->defaults['action'].
     *
     * @return array
     *
     */
    protected function getSpec($action = null)
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
            'resourceCallable'
        );

        $spec = array();
        foreach ($vars as $var) {
            $spec[$var] = $this->$var;
        }

        if ($action) {
            $spec['defaults']['action'] = $action;
        }

        return $spec;
    }

    /**
     *
     * Sets the existing default route specification.
     *
     * @param array $spec The new default route specification.
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
    /**
     *
     * Use the `$resourceCallable` to attach a resource.
     *
     * @param string $name The resource name; used as a route name prefix.
     *
     * @param string $path The path to the resource; used as a route path
     * prefix.
     *
     * @return null
     *
     */
    public function attachResource($name, $path)
    {
        $this->attach($name, $path, $this->resourceCallable);
    }

    /**
     *
     * Sets the callable for attaching resource routes.
     *
     * @param callable $resource The resource callable.
     *
     * @return $this
     *
     */
    public function setResourceCallable($resource)
    {
        $this->resourceCallable = $resource;
        return $this;
    }

    /**
     *
     * Callable for `attachResource()` that adds resource routes.
     *
     * @param Map $map A Map, probably $this.
     *
     * @return null
     *
     */
    protected function resourceCallable(Map $map)
    {
        // add 'id' and 'format' if not already defined
        $tokens = array();
        if (! isset($map->tokens['id'])) {
            $tokens['id'] = '\d+';
        }
        if (! isset($map->tokens['format'])) {
            $tokens['format'] = '(\.[^/]+)?';
        }
        if ($tokens) {
            $map->addTokens($tokens);
        }

        // add the routes
        $map->addGet('browse', '{format}');
        $map->addGet('read', '/{id}{format}');
        $map->addGet('edit', '/{id}/edit{format}');
        $map->addGet('add', '/add');
        $map->addDelete('delete', '/{id}');
        $map->addPost('create', '');
        $map->addPatch('update', '/{id}');
        $map->addPut('replace', '/{id}');
        $map->addOptions('options', '');
    }
}
