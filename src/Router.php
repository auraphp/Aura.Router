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
     * Prefix route names with this name.
     * 
     * @var string
     * 
     * @see attach()
     * 
     */
    protected $name_prefix;
    
    /**
     * 
     * Capture the un-prefixed route name as a default value for this param.
     * 
     * @var string
     * 
     */
    protected $name_param;
    
    /**
     * 
     * Prefix route paths with this path.
     * 
     * @var string
     * 
     * @see attach()
     * 
     */
    protected $path_prefix;
    
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
	    'require'     => array(),
	    'default'     => array(),
	    'secure'      => null,
	    'wildcard'    => null,
	    'routable'    => true,
	    'is_match'    => null,
	    'generate'    => null,
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
     * Sets the param into which the un-prefixed route name should be
     * captured.
     * 
     * @param string $name_param The param into which the name should be
     * captured.
     * 
     * @return null
     * 
     */
    public function useNameAsParam($name_param)
    {
        $this->name_param = $name_param;
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
    public function add($name, $path, array $spec = array())
    {
        // merge with default route spec
        $spec = array_merge_recursive($this->spec, $spec);
        
        // add the name prefix, but only if a name is given
        $spec['name'] = ($name) ? $this->name_prefix . $name : $name;
        
        // always add the path prefix
        $spec['path'] = $this->path_prefix . $path;
        
        // capture the un-prefixed name as a default param value
        $capture = $this->name_param
                && ! isset($spec['default'][$this->name_param]);
        if ($capture) {
            $spec['default'][$this->name_param] = $name;
        }
        
        // create the route
        $route = $this->route_factory->newInstance($spec);
        
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
    public function addGet($name, $path, array $spec = array())
    {
        $spec['require']['REQUEST_METHOD'] = 'GET';
        return $this->add($name, $path, $spec);
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
    public function addDelete($name, $path, array $spec = array())
    {
        $spec['require']['REQUEST_METHOD'] = 'DELETE';
        return $this->add($name, $path, $spec);
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
    public function addOptions($name, $path, array $spec = array())
    {
        $spec['require']['REQUEST_METHOD'] = 'OPTIONS';
        return $this->add($name, $path, $spec);
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
    public function addPatch($name, $path, array $spec = array())
    {
        $spec['require']['REQUEST_METHOD'] = 'PATCH';
        return $this->add($name, $path, $spec);
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
    public function addPost($name, $path, array $spec = array())
    {
        $spec['require']['REQUEST_METHOD'] = 'POST';
        return $this->add($name, $path, $spec);
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
    public function addPut($name, $path, array $spec = array())
    {
        $spec['require']['REQUEST_METHOD'] = 'PUT';
        return $this->add($name, $path, $spec);
    }
    
    /**
     * 
     * Attaches routes to a specific path prefix and prefixes the attached 
     * route names.
     * 
     * @param string $name_prefix The prefix for all route names being
     * attached.
     * 
     * @param string $path_prefix The prefix for all route paths being
     * attached.
     * 
     * @param callable $callable A callable that uses the Router to add new
     * routes. Its signature is `function (\Aura\Router\Router $router)`; this
     * Router instance will be passed to the callable.
     * 
     * @return null
     * 
     */
    public function attach($name_prefix, $path_prefix, $callable)
    {
        // save previous values
        $old_name_prefix = $this->name_prefix;
        $old_path_prefix = $this->path_prefix;
        $old_spec        = $this->spec;
        $old_name_param  = $this->name_param;
        
        // append to the current prefixes
        $this->name_prefix .= $name_prefix;
        $this->path_prefix .= $path_prefix;
        
        // invoke the callable, passing this Router as the only param
        call_user_func($callable, $this);
        
        // restore previous values which the callable may have modified
        $this->name_prefix = $old_name_prefix;
        $this->path_prefix = $old_path_prefix;
        $this->spec        = $old_spec;
        $this->name_param  = $old_name_param;
    }
    
    /**
     * 
     * Sets the regular expressions that params must match.
     * 
     * @param array $require Params are required to match these expressions.
     * 
     * @return null
     * 
     */
    public function setRequire(array $require)
    {
        $this->spec['require'] = $require;
    }
    
    /**
     * 
     * Sets the default values for params.
     * 
     * @param array $default Default values for params.
     * 
     * @return null
     * 
     */
    public function setDefault(array $default)
    {
        $this->spec['default'] = $default;
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
}
