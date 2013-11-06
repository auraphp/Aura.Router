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
 * A collection point for URI routes.
 * 
 * @package Aura.Router
 * 
 */
class Router
{
    /**
     * 
     * Currently processing this attached common route information.
     * 
     * @var array
     * 
     */
    protected $attach_common = array();

    /**
     * 
     * Currently processing these attached routes.
     * 
     * @var array
     * 
     */
    protected $attach_routes = array();

    /**
     * 
     * Route definitions; these will be converted into objects.
     * 
     * @var array
     * 
     */
    protected $definitions = array();

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
     * A copy of the $_SERVER array.
     * 
     * @var array
     * 
     */
    protected $server;
    
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
     * Constructor.
     * 
     * @param RouteFactory $route_factory A factory for creating definition
     * and route objects.
     * 
     * @param array $attach A series of route definitions to be attached to
     * the router.
     * 
     */
    public function __construct(
        RouteFactory $route_factory,
        array $attach = null
    ) {
        $this->route_factory = $route_factory;
        foreach ((array) $attach as $path_prefix => $spec) {
            $this->attach($path_prefix, $spec);
        }
    }

    /**
     * 
     * Adds a single route definition to the stack.
     * 
     * @param string $name The route name for `generate()` lookups.
     * 
     * @param string $path The route path.
     * 
     * @param array $spec The rest of the route definition, with keys for
     * `params`, `values`, etc.
     * 
     * @return null
     * 
     */
    public function add($name, $path, array $spec = null)
    {
        $spec = (array) $spec;

        // overwrite the name and path
        $spec['name'] = $name;
        $spec['path'] = $path;

        // these should be set only by the router
        unset($spec['name_prefix']);
        unset($spec['path_prefix']);

        // append to the route definitions
        $this->definitions[] = $this->route_factory->newDefinition(
            'single',
            $spec
        );
    }

    /**
     * 
     * Attaches several routes at once to a specific path prefix.
     * 
     * @param string $path_prefix The path that the routes should be attached
     * to.
     * 
     * @param array $spec An array of common route information, with an
     * additional `routes` key to define the routes themselves.
     * 
     * @return null
     * 
     */
    public function attach($path_prefix, $spec)
    {
        $this->definitions[] = $this->route_factory->newDefinition(
            'attach',
            $spec,
            $path_prefix
        );
    }

    /**
     * 
     * Gets a route that matches a given path and other server conditions.
     * 
     * @param string $path The path to match against.
     * 
     * @param array $server A copy of the $_SERVER superglobal.
     * 
     * @return Route|false Returns a Route object when it finds a match, or 
     * boolean false if there is no match.
     * 
     */
    public function match($path, array $server = array())
    {
        // reset the log
        $this->log = array();

        // look through existing route objects
        foreach ($this->routes as $route) {
            $this->logRoute($route);
            if ($route->isMatch($path, $server)) {
                return $route;
            }
        }

        // convert remaining definitions as needed
        while ($this->attach_routes || $this->definitions) {
            $route = $this->createNextRoute();
            $this->logRoute($route);
            if ($route->isMatch($path, $server)) {
                return $route;
            }
        }

        // no joy
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
        // do we already have the route object?
        if (isset($this->routes[$name])) {
            return $this->routes[$name]->generate($data);
        }

        // convert remaining definitions as needed
        while ($this->attach_routes || $this->definitions) {
            $route = $this->createNextRoute();
            if ($route->name == $name) {
                return $route->generate($data);
            }
        }

        // no joy
        throw new Exception\RouteNotFound($name);
    }

    /**
     * 
     * Reset the Router to use an array of Route objects.
     * 
     * @param array $routes Use this array of route objects, likely generated
     * from `getRoutes()`.
     * 
     * @return null
     * 
     */
    public function setRoutes(array $routes)
    {
        $this->routes = $routes;
        $this->definitions = array();
        $this->attach_common = array();
        $this->attach_routes = array();
    }

    /**
     * 
     * Get the array of Route objects in this Router, likely for caching and
     * re-setting via `setRoutes()`.
     * 
     * @return array
     * 
     */
    public function getRoutes()
    {
        // convert remaining definitions as needed
        while ($this->attach_routes || $this->definitions) {
            $this->createNextRoute();
        }
        return $this->routes;
    }

    /**
     * 
     * Get the log of attempted route matches.
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
     * Add a route to the log of attempted matches.
     * 
     * @param Route $route Route object
     * 
     * @return array
     * 
     */
    protected function logRoute(Route $route)
    {
        $this->log[] = $route;
    }

    /**
     * 
     * Gets the next Route object in the stack, converting definitions to 
     * Route objects as needed.
     * 
     * @return Route|false A Route object, or boolean false at the end of the 
     * stack.
     * 
     */
    protected function createNextRoute()
    {
        // do we have attached routes left to process?
        if ($this->attach_routes) {
            // yes, get the next attached definition
            $spec = $this->getNextAttach();
        } else {
            // no, get the next unattached definition
            $spec = $this->getNextDefinition();
        }

        // create a route object from it
        $route = $this->route_factory->newRoute($spec);

        // retain the route object ...
        $name = $route->name;
        if ($name) {
            // ... under its name so we can look it up later
            $this->routes[$name] = $route;
        } else {
            // ... under no name, which means we can't look it up later
            $this->routes[] = $route;
        }

        // return whatever route got retained
        return $route;
    }

    /**
     * 
     * Gets the next route definition from the stack.
     * 
     * @return array A route definition.
     * 
     */
    protected function getNextDefinition()
    {
        // get the next definition and extract the definition type
        $def  = array_shift($this->definitions);
        $spec = $def->getSpec();
        $type = $def->getType();

        // is it a 'single' definition type?
        if ($type == 'single') {
            // done!
            return $spec;
        }

        // it's an 'attach' definition; set up for attach processing.
        // retain the routes from the array ...
        $this->attach_routes = $spec['routes'];
        unset($spec['routes']);

        // ... and the remaining common information
        $this->attach_common = $spec;
        
        // reset the internal pointer of the array to avoid misnamed routes
        reset($this->attach_routes);
        
        // now get the next attached route
        return $this->getNextAttach();
    }

    /**
     * 
     * Gets the next attached route definition.
     * 
     * @return array A route definition.
     * 
     */
    protected function getNextAttach()
    {
        $key = key($this->attach_routes);
        $val = array_shift($this->attach_routes);

        // which definition form are we using?
        if (is_string($key) && is_string($val)) {
            // short form, named in key
            $spec = array(
                'name' => $key,
                'path' => $val,
                'values' => array(
                    'action' => $key,
                ),
            );
        } elseif (is_int($key) && is_string($val)) {
            // short form, no name
            $spec = array(
                'path' => $val,
            );
        } elseif (is_string($key) && is_array($val)) {
            // long form, named in key
            $spec = $val;
            $spec['name'] = $key;
            // if no action, use key
            if (! isset($spec['values']['action'])) {
                $spec['values']['action'] = $key;
            }
        } elseif (is_int($key) && is_array($val)) {
            // long form, no name
            $spec = $val;
        } else {
            throw new Exception\UnexpectedType("Route spec for '$key' should be a string or array.");
        }

        // unset any path or name prefix on the spec itself
        unset($spec['name_prefix']);
        unset($spec['path_prefix']);

        // now merge with the attach info
        $spec = array_merge_recursive($this->attach_common, $spec);

        // done!
        return $spec;
    }
}
