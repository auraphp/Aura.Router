<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace aura\router;

/**
 * 
 * A collection point for URI routes.
 * 
 * @package aura.router
 * 
 */
class Map
{
    /**
     * 
     * Currently processing this attached common route information.
     * 
     * @var array
     * 
     */
    protected $attach_common = null;
    
    /**
     * 
     * Currently processing these attached routes.
     * 
     * @var array
     * 
     */
    protected $attach_routes = null;
    
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
    protected $factory;
    
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
     * Constructor.
     * 
     * @param RouteFactory $factory A factory for creating route objects.
     * 
     * @param array $attach A series of route definitions to be attached to
     * the router.
     * 
     */
    public function __construct(
        RouteFactory $factory,
        array $attach = null
    ) {
        $this->factory = $factory;
        foreach ((array) $attach as $path_prefix => $spec) {
            $this->attach($path_prefix, $spec);
        }
    }
    
    /**
     * 
     * Adds a single route definition to the stack.
     * 
     * @param string $name The route name for `getPath()` lookups.
     * 
     * @param string $path The route path.
     * 
     * @param array $spec The rest of the route definition, with keys for
     * `params`, `values`, etc.
     * 
     * @return void
     * 
     */
    public function add($name, $path, array $spec = null)
    {
        $spec = (array) $spec;
        
        // overwrite the name and path
        $spec['name'] = $name;
        $spec['path'] = $path;
        
        // these should be set only by the map
        unset($spec['name_prefix']);
        unset($spec['path_prefix']);
        
        // append to the route definitions
        $this->append('single', $spec);
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
     * @return void
     * 
     */
    public function attach($path_prefix, array $spec)
    {
        // ... with routes defined for attachment.
        if (! isset($spec['routes'])) {
            throw new \UnexpectedValueException('No routes defined for attachment.');
        }
        
        // set the path_prefix in the specification
        $spec['path_prefix'] = $path_prefix;
        
        // append to the definitions
        $this->append('attach', $spec);
    }
    
    /**
     * 
     * Gets a route that matches a given path and other server conditions.
     * 
     * @param string $path The path to match against.
     * 
     * @param array $server An array copy of $_SERVER.
     * 
     * @return Route|false Returns a Route object when it finds a match, or 
     * boolean false if there is no match.
     * 
     */
    public function getRoute($path, array $server = null)
    {
        reset($this->routes);
        while ($route = $this->getNextRoute()) {
            $result = $route->isMatch($path, $server);
            if ($result) {
                return $result;
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
     * @param array $data The data to inpterolate into the URI; data keys
     * map to param tokens in the path.
     * 
     * @return string|false A URI path string if the route name is found, or
     * boolean false if not.
     * 
     */
    public function getPath($name, $data = null)
    {
        // do we already have the route object?
        if (isset($this->routes[$name])) {
            return $this->routes[$name]->getPath($data);
        }
        
        // are there routes left to convert to objects?
        if (! $this->definitions) {
            // no, which means there is no matching route name
            return false;
        }
        
        // create objects from route specs and check the names
        while ($route = $this->getNextRoute()) {
            if ($route->name == $name) {
                return $route->getPath($data);
            }
        }
        
        // no joy
        return false;
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
    protected function getNextRoute()
    {
        // do we have a current route?
        $route = current($this->routes);
        if ($route) {
            // advance the pointer for next time ...
            next($this->routes);
            // ... and return the current route.
            return $route;
        }
        
        // are there any route definitions left to convert to objects?
        if (! $this->definitions && ! $this->attach_routes) {
            // no, so we're done
            return false;
        }
        
        // get the next route definition and create a route object from it
        $spec = $this->getNextDefinition();
        $route = $this->factory->newInstance($spec);
        
        // retain the route object ...
        $name = $route->name;
        if ($name) {
            // ... under its name so we can look it up later
            $this->routes[$name] = $route;
        } else {
            // ... under no name, which means we can't look it up later
            $this->routes[] = $route;
        }
        
        // return whatever route got added next
        return $route;
    }
    
    /**
     * 
     * Appends a single route definition to the stack.
     * 
     * @param array $type The definition type: a 'single' route, or 'attach' a
     * group of routes.
     * 
     * @param array $spec The definition itself.
     * 
     * @return void
     * 
     */
    protected function append($type, $spec)
    {
        $spec['__type'] = $type;
        $this->definitions[] = $spec;
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
        // are there attached routes left to process?
        if ($this->attach_routes) {
            return $this->getNextAttach();
        }
        
        // get the next definition and extract the definition type
        $spec = array_shift($this->definitions);
        $type = $spec['__type'];
        unset($spec['__type']);
        
        // is it a 'single' definition type?
        if ($type == 'single') {
            // done!
            return $spec;
        }
        
        // it's an 'attach' definition; set up for attach processing.
        // retain the routes ...
        $this->attach_routes = $spec['routes'];
        unset($spec['routes']);
        
        // ... and the remaining common information
        $this->attach_common = $spec;
        
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
            throw new \UnexpectedValueException("Route spec for '$key' should be a string or array.");
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
