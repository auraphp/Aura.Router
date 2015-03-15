<?php
/**
 *
 * This file is part of the Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

use ArrayAccess;
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
class RouteCollection extends AbstractSpec implements
    ArrayAccess,
    Countable,
    IteratorAggregate
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
    protected $route_factory;

    /**
     *
     * A prefix to add to each route name added to the collection.
     *
     * @var string
     *
     */
    protected $name_prefix = null;

    /**
     *
     * A prefix to add to each route path added to the collection.
     *
     * @var string
     *
     */
    protected $path_prefix = null;

    /**
     *
     * A callable to use for each resource attached to the collection.
     *
     * @var callable
     *
     * @see attachResource()
     *
     */
    protected $resource_callable = null;

    /**
     *
     * A callable to modify to each route added to the collection.
     *
     * @var callable
     *
     * @see add()
     *
     */
    protected $route_callable = null;

    /**
     *
     * Constructor.
     *
     * @param RouteFactory $route_factory A factory to create route objects.
     *
     * @param array $routes An array of route objects.
     *
     */
    public function __construct(
        RouteFactory $route_factory,
        array $routes = array()
    ) {
        $this->route_factory = $route_factory;
        $this->routes = $routes;
        $this->setResourceCallable(array($this, 'resourceCallable'));
        $this->setRouteCallable(array($this, 'routeCallable'));
    }

    /**
     *
     * ArrayAccess: gets a route by name.
     *
     * @param string $name The route name.
     *
     * @return Route
     *
     */
    public function offsetGet($name)
    {
        return $this->routes[$name];
    }

    /**
     *
     * ArrayAccess: sets a route by name.
     *
     * @param string $name The route name.
     *
     * @param Route $route The route object.
     *
     * @return null
     *
     */
    public function offsetSet($name, $route)
    {
        if (! $route instanceof Route) {
            throw new Exception\UnexpectedValue;
        }

        $this->routes[$name] = $route;
    }

    /**
     *
     * ArrayAccess: does a route name exist?
     *
     * @param string $name The route name.
     *
     * @return bool
     *
     */
    public function offsetExists($name)
    {
        return isset($this->routes[$name]);
    }

    /**
     *
     * ArrayAccess: removes a route by name.
     *
     * @param string $name The route name to remove.
     *
     * @return null
     *
     */
    public function offsetUnset($name)
    {
        unset($this->routes[$name]);
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
     * @param mixed $action A value for $route->values['action'].
     *
     * @return Route The newly-added route object.
     *
     */
    public function add($name, $path, $action = null)
    {
        // create the route with the full path, name, and spec
        $route = $this->route_factory->newInstance(
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

        // modify newly-added route
        call_user_func($this->route_callable, $route);

        // done; return for further modification
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
     * @param mixed $action A value for $route->values['action'].
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
     * @param mixed $action A value for $route->values['action'].
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
     * @param mixed $action A value for $route->values['action'].
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
     * @param mixed $action A value for $route->values['action'].
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
     * @param mixed $action A value for $route->values['action'].
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
     * @param mixed $action A value for $route->values['action'].
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
     * @param mixed $action A value for $route->values['action'].
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
     * Sets the callable for modifying a newly-added route before it is
     * returned.
     *
     * @param callable $callable The callable to modify the route.
     *
     * @return $this
     *
     */
    public function setRouteCallable($callable)
    {
        $this->route_callable = $callable;
        return $this;
    }

    /**
     *
     * Modifies the newly-added route to set an 'action' value from the route
     * name.
     *
     * @param Route $route The newly-added route.
     *
     * @return null
     *
     */
    protected function routeCallable(Route $route)
    {
        if ($route->name && ! isset($route->values['action'])) {
            $route->addValues(array('action' => $route->name));
        }
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
     * routes. Its signature is `function (\Aura\Router\Router $router)`; this
     * Router instance will be passed to the callable.
     *
     * @return null
     *
     */
    public function attach($name, $path, $callable)
    {
        // save current spec
        $spec = $this->getSpec();

        // append to the name prefix, with delimiter if needed
        if ($this->name_prefix) {
            $this->name_prefix .= '.';
        }
        $this->name_prefix .= $name;

        // append to the path prefix
        $this->path_prefix .= $path;

        // invoke the callable, passing this RouteCollection as the only param
        call_user_func($callable, $this);

        // restore previous spec
        $this->setSpec($spec);
    }

    /**
     *
     * Gets the existing default route specification.
     *
     * @param mixed $action A value for $route->values['action'].
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
            'values',
            'secure',
            'wildcard',
            'routable',
            'is_match',
            'generate',
            'name_prefix',
            'path_prefix',
            'resource_callable',
            'route_callable',
        );

        $spec = array();
        foreach ($vars as $var) {
            $spec[$var] = $this->$var;
        }

        if ($action) {
            $spec['values']['action'] = $action;
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
        $this->attach($name, $path, $this->resource_callable);
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
        $this->resource_callable = $resource;
        return $this;
    }

    /**
     *
     * Callable for `attachResource()` that adds resource routes.
     *
     * @param RouteCollection $router A RouteCollection, probably $this.
     *
     * @return null
     *
     */
    protected function resourceCallable(RouteCollection $router)
    {
        // add 'id' and 'format' if not already defined
        $tokens = array();
        if (! isset($router->tokens['id'])) {
            $tokens['id'] = '\d+';
        }
        if (! isset($router->tokens['format'])) {
            $tokens['format'] = '(\.[^/]+)?';
        }
        if ($tokens) {
            $router->addTokens($tokens);
        }

        // add the routes
        $router->addGet('browse', '{format}');
        $router->addGet('read', '/{id}{format}');
        $router->addGet('edit', '/{id}/edit{format}');
        $router->addGet('add', '/add');
        $router->addDelete('delete', '/{id}');
        $router->addPost('create', '');
        $router->addPatch('update', '/{id}');
        $router->addPut('replace', '/{id}');
        $router->addOptions('options', '');
    }
}
