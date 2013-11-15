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
	 * An array of default route specifications.
	 * 
	 * @var array
	 * 
	 */
	protected $spec = array(
	    'tokens'      => array(),
	    'server'      => array(),
	    'values'      => array(),
	    'secure'      => null,
	    'wildcard'    => null,
	    'routable'    => true,
	    'is_match'    => null,
	    'generate'    => null,
	    'name_prefix' => null,
	    'path_prefix' => null,
	    'resource'    => null,
	);
	
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
     * @return Route The newly-added route object.
     * 
     */
    public function add($name, $path)
    {
        // build a full name with prefix, but only if name is given
        $full_name = ($this->spec['name_prefix'] && $name)
                   ? $this->spec['name_prefix'] . '.' . $name
                   : $name;
        
        // build a full path with prefix
        $full_path = $this->spec['path_prefix'] . $path;
        
        // create the route with the full path and name
        $route = $this->route_factory->newInstance($full_path, $full_name);
        
        // add controller and action values
        $route->addValues(array(
            'controller' => $this->spec['name_prefix'],
            'action' => $name,
        ));
        
        // set default specs from router, which override the automatic
        // controller and action values
        $route->addTokens($this->spec['tokens']);
        $route->addServer($this->spec['server']);
        $route->addValues($this->spec['values']);
        $route->setSecure($this->spec['secure']);
        $route->setWildcard($this->spec['wildcard']);
        $route->setRoutable($this->spec['routable']);
        $route->setIsMatchCallable($this->spec['is_match']);
        $route->setGenerateCallable($this->spec['generate']);
        
        // add the route under its full name
        if (! $route->name) {
            $this->routes[] = $route;
        } else {
            $this->routes[$route->name] = $route;
        }
        
        // done!
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
     * @return Route The newly-added route object.
     * 
     */
    public function addGet($name, $path)
    {
        $route = $this->add($name, $path);
        $route->addServer(array('REQUEST_METHOD' => 'GET'));
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
     * @return Route The newly-added route object.
     * 
     */
    public function addDelete($name, $path)
    {
        $route = $this->add($name, $path);
        $route->addServer(array('REQUEST_METHOD' => 'DELETE'));
        return $route;
    }
    
    /**
     * 
     * Adds an Options route.
     * 
     * @param string $name The route name.
     * 
     * @param string $path The route path.
     * 
     * @return Route The newly-added route object.
     * 
     */
    public function addOptions($name, $path)
    {
        $route = $this->add($name, $path);
        $route->addServer(array('REQUEST_METHOD' => 'OPTIONS'));
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
     * @return Route The newly-added route object.
     * 
     */
    public function addPatch($name, $path)
    {
        $route = $this->add($name, $path);
        $route->addServer(array('REQUEST_METHOD' => 'PATCH'));
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
     * @return Route The newly-added route object.
     * 
     */
    public function addPost($name, $path)
    {
        $route = $this->add($name, $path);
        $route->addServer(array('REQUEST_METHOD' => 'POST'));
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
     * @return Route The newly-added route object.
     * 
     */
    public function addPut($name, $path)
    {
        $route = $this->add($name, $path);
        $route->addServer(array('REQUEST_METHOD' => 'PUT'));
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
     * routes. Its signature is `function (\Aura\Router\Router $router)`; this
     * Router instance will be passed to the callable.
     * 
     * @return null
     * 
     */
    public function attach($name, $path, $callable)
    {
        // save current spec
        $old_spec = $this->spec;
        
        // append to the name prefix, with delmiter if needed
        if ($this->spec['name_prefix']) {
            $this->spec['name_prefix'] .= '.';
        }
        $this->spec['name_prefix'] .= $name;
        
        // append to the path prefix
        $this->spec['path_prefix'] .= $path;
        
        // invoke the callable, passing this Router as the only param
        call_user_func($callable, $this);
        
        // restore previous spec
        $this->spec = $old_spec;
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
        $this->attach($name, $path, $this->spec['resource']);
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
        $this->spec['resource'] = $resource;
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
        // browse the resources, optionally in a format.
        // can double for search when a query string is passed.
        $router->addGet('browse', '{format}')
            ->addTokens(array(
                'format' => '(\.[^/]+)?',
            ));

        // get a single resource by ID, optionally in a format
        $router->addGet('read', '/{id}{format}')
            ->addTokens(array(
                'format' => '(\.[^/]+)?',
            ));

        // get the form to add new resource
        $router->addGet('add', '/add');

        // get the form for an existing resource by ID, optionally in a format
        $router->addGet('edit', '/{id}/edit{format}')
            ->addTokens(array(
                'format' => '(\.[^/]+)?',
            ));

        // delete a resource by ID
        $router->addDelete('delete', '/{id}');

        // create a resource and get back its location
        $router->addPost('create', '');

        // update part or all an existing resource by ID
        $router->addPatch('update', '/{id}');
        
        // replace an existing resource by ID
        $router->addPut('replace', '/{id}');
    }
}
