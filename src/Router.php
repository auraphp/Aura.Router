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
 * A collection of routes to be matched.
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
    protected $log = array();

    /**
     * 
     * A RouteFactory for creating route objects.
     * 
     * @var RouteFactory
     * 
     */
    protected $route_factory;

    /**
     * 
     * Route objects created from the definitons.
     * 
     * @var array
     * 
     */
    protected $routes = array();

    /**
	 * 
	 * An array of default route specifications.
	 * 
	 * @var array
	 * 
	 */
	protected $spec = array(
	    'name'        => null,
	    'path'        => null,
	    'tokens'      => array(),
	    'server'      => array(),
	    'values'      => array(),
	    'secure'      => null,
	    'wildcard'    => null,
	    'routable'    => true,
	    'is_match'    => null,
	    'generate'    => null,
	    'name_param'  => null,
	    'name_delim'  => '.',
	    'name_prefix' => null,
	    'path_prefix' => null,
	);
	
    /**
     * 
     * Constructor.
     * 
     * @param RouteFactory $route_factory A factory for route objects.
     * 
     */
    public function __construct(RouteFactory $route_factory)
    {
        $this->route_factory = $route_factory;
        $this->setResourceCallable(array($this, 'resourceCallable'));
    }
    
    public function setResourceCallable($resource)
    {
        $this->resource = $resource;
    }
    
    /**
     * 
     * Sets the array of route objects to use.
     * 
     * @param array $routes Use this array of route objects.
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
     * Gets the array of route objects in this router, likely for caching and
     * re-setting via `setRoutes()`.
     * 
     * @return array
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
     * Gets the log of attempted route matches.
     * 
     * @return array
     * 
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * 
     * Gets the current name prefix for routes.
     * 
     * @return string
     * 
     */
    public function getNamePrefix()
    {
        return $this->spec['name_prefix'];
    }
    
    /**
     * 
     * Returns the curent path prefix for routes.
     * 
     * @return string
     * 
     */
    public function getPathPrefix()
    {
        return $this->spec['path_prefix'];
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
                   ? $this->spec['name_prefix'] . $this->spec['name_delim'] . $name
                   : $name;
        
        // build a full path with prefix
        $full_path = $this->getPathPrefix() . $path;
        
        // create the route with the full path and name
        $route = $this->route_factory->newInstance($full_path, $full_name);
        
        // set default specs
        $route->addTokens($this->spec['tokens']);
        $route->addServer($this->spec['server']);
        $route->addValues($this->spec['values']);
        $route->setSecure($this->spec['secure']);
        $route->setWildcard($this->spec['wildcard']);
        $route->setRoutable($this->spec['routable']);
        $route->setIsMatchCallable($this->spec['is_match']);
        $route->setGenerateCallable($this->spec['generate']);
        
        // capture the un-prefixed name as a default param value?
        $name_param = $this->spec['name_param'];
        if ($name_param && ! isset($route->values[$name_param])) {
            $route->addValues(array($name_param => $name));
        }
        
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
            $this->spec['name_prefix'] .= $this->spec['name_delim'];
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
        $this->attach($name, $path, $this->resource);
    }
    
    /**
     * 
     * Sets the regular expressions for param tokens.
     * 
     * @param array $tokens The regular expressions for param tokens.
     * 
     * @return null
     * 
     */
    public function setTokens(array $tokens)
    {
        $this->spec['tokens'] = $tokens;
    }
    
    /**
     * 
     * Sets the regular expressions for server values.
     * 
     * @param array $server The regular expressions for server values.
     * 
     * @return null
     * 
     */
    public function setServer(array $server)
    {
        $this->spec['server'] = $server;
    }
    
    /**
     * 
     * Sets the default values for params.
     * 
     * @param array $values Default values for params.
     * 
     * @return null
     * 
     */
    public function setValues(array $values)
    {
        $this->spec['values'] = $values;
    }
    
    /**
     * 
     * Sets whether or not the route must be secure.
     * 
     * @param bool $secure If true, the server must indicate an HTTPS request;
     * if false, it must *not* be HTTPS; if null, it doesn't matter.
     * 
     * @return null
     * 
     */
    public function setSecure($secure = true)
    {
        $this->spec['secure'] = ($secure === null) ? null : (bool) $secure;
    }
    
    /**
     * 
     * Sets the name of the wildcard param.
     * 
     * @param string $wildcard The name of the wildcard param, if any.
     * 
     * @return null
     * 
     */
    public function setWildcard($wildcard)
    {
        $this->spec['wildcard'] = $wildcard;
    }
    
    /**
     * 
     * Sets whether or not this route should be used for matching.
     * 
     * @param bool $routable If true, this route can be matched; if not, it
     * can be used only to generate a path.
     * 
     * @return null
     * 
     */
    public function setRoutable($routable = true)
    {
        $this->spec['routable'] = (bool) $routable;
    }
    
    /**
     * 
     * Sets a custom callable to evaluate the route for matching.
     * 
     * @param callable $is_match A custom callable to evaluate the route.
     * 
     * @return null
     * 
     */
    public function setIsMatchCallable($is_match)
    {
        $this->spec['is_match'] = $is_match;
    }
    
    /**
     * 
     * Sets a custom callable to modify data for `generate()`.
     * 
     * @param callable $generate A custom callable to modify data for
     * `generate()`.
     * 
     * @return null
     * 
     */
    public function setGenerateCallable($generate)
    {
        $this->spec['generate'] = $generate;
    }
    
    /**
     * 
     * Sets the param into which the un-prefixed route name should be
     * captured.
     * 
     * @param string $name_param The param into which the name should be
     * captured.
     * 
     * @return null
     * 
     */
    public function setNameParam($name_param)
    {
        $this->spec['name_param'] = $name_param;
    }
    
    /**
     * 
     * Sets the delmiter between the route name prefix and the route name.
     * 
     * @param string $name_delim The delimiter to use.
     * 
     * @return null
     * 
     */
    public function setNameDelim($name_delim)
    {
        $this->spec['name_delim'] = $name_delim;
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
        $this->log = array();

        foreach ($this->routes as $route) {
            $match = $route->isMatch($path, $server);
            $this->log[] = $route;
            if ($match) {
                return $route;
            }
        }
        
        return false;
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
        if (! isset($this->routes[$name])) {
            throw new Exception\RouteNotFound($name);
        }
        
        return $this->routes[$name]->generate($data);
    }
    
    /**
     * 
     * Callable for `attachResource()` that adds resource routes.
     * 
     * @param Router $router A Router instance, probably $this.
     * 
     * @return null
     * 
     */
    protected function resourceCallable(Router $router)
    {
        // use the route name as the action param
        $router->setNameParam('action');

        // browse the resources, optionally in a format.
        // can double for search when a query string is passed.
        $router->addGet('browse', '{format}')
            ->addTokens(array(
                'format' => '(\.[^/]+)?',
            ))
            ->addValues(array(
                'controller' => $router->getNamePrefix(),
            ));

        // get a single resource by ID, optionally in a format
        $router->addGet('read', '/{id}{format}')
            ->addTokens(array(
                'format' => '(\.[^/]+)?',
            ))
            ->addValues(array(
                'controller' => $router->getNamePrefix(),
            ));

        // add a new resource and get back its location
        $router->addPost('add', '')
            ->addValues(array(
                'controller' => $router->getNamePrefix(),
            ));

        // get the form for an existing resource by ID, optionally in a format
        $router->addGet('edit', '/{id}/edit{format}')
            ->addTokens(array(
                'format' => '(\.[^/]+)?',
            ))
            ->addValues(array(
                'controller' => $router->getNamePrefix(),
            ));

        // delete a resource by ID
        $router->addDelete('delete', '/{id}')
            ->addValues(array(
                'controller' => $router->getNamePrefix(),
            ));

        // get the form for a new resource
        $router->addGet('new', '/new')
            ->addValues(array(
                'controller' => $router->getNamePrefix(),
            ));

        // update an existing resource by ID
        $router->add('update', '/{id}')
            ->addValues(array(
                'controller' => $router->getNamePrefix(),
            ))
            ->addServer(array(
                'REQUEST_METHOD' => 'PUT|PATCH'
            ));
        
        // done!
        return;
    }
}
