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
 * Represents an individual route with a name, path, params, values, etc.
 *
 * In general, you should never need to instantiate a Route directly. Use the
 * RouteFactory instead, or the Map.
 * 
 * @package aura.router
 * 
 */
class Route
{
    /**
     * 
     * The name for this Route.
     * 
     * @var string
     * 
     */
    protected $name;
    
    /**
     * 
     * The path for this Route with param tokens.
     * 
     * @var string
     * 
     */
    protected $path;
    
    /**
     * 
     * A map of param tokens to their regex subpatterns.
     * 
     * @var array
     * 
     */
    protected $params = array();
    
    /**
     * 
     * A map of param tokens to their default values; if this Route is
     * matched, these will retain the corresponding values from the param 
     * tokens in the matching path.
     * 
     * @var array
     * 
     */
    protected $values = array();
    
    /**
     * 
     * The `REQUEST_METHOD` value must match one of the methods in this array;
     * method; e.g., `'GET'` or `array('POST', 'DELETE')`.
     * 
     * @var array
     * 
     */
    protected $method = array();
     
    /**
     * 
     * When true, the `HTTPS` value must be `on`, or the `SERVER_PORT` must be
     * 443.  When false, neither of those values may be present.  When null, 
     * it is ignored.
     * 
     * @var bool
     * 
     */
    protected $secure = null;
    
    /**
     * 
     * A closure to provide custom matching logic against the server 
     * values and matched params from this Route. The function signature 
     * must be `function($server, &$matches)` and must return a boolean: 
     * true to accept this Route match, or false to deny the match. Note that 
     * this allows a wide range of manipulations, and further allows the 
     * developer to modify the matched params.
     * 
     * @var \Closure
     * 
     * @see isMatch()
     * 
     */
    protected $is_match;
    
    /**
     * 
     * A closure to modify path-generation values. The function signature must
     * be `function($route, array &$data)`; its return value is discarded. The
     * `$route` is this Route object, and `&$data` is a set of key-value pairs
     * to be interpolated into the path.
     * 
     * @var \Closure
     * 
     * @see getPath()
     * 
     */
    protected $get_path;
    
    /**
     * 
     * A prefix for the Route name, generally from attached route groups.
     * 
     * @var string
     * 
     */
    protected $name_prefix;
    
    /**
     * 
     * A prefix for the Route path, generally from attached route groups.
     * 
     * @var string
     * 
     */
    protected $path_prefix;
    
    /**
     * 
     * The $path property converted to a regular expression, using the $params
     * subpatterns.
     * 
     * @var string
     * 
     */
    protected $regex;
    
    /**
     * 
     * All param matches found in the path during the `isMatch()` process.
     * 
     * @var string
     * 
     * @see isMatch()
     * 
     */
    protected $matches;
    
    /**
     * 
     * Constructor.
     * 
     * @param string $name The name for this Route.
     * 
     * @param string $path The path for this Route with param token placeholders.
     * 
     * @param array $params Map of param tokens to regex subpatterns.
     * 
     * @param array $values Default values for params.
     * 
     * @param string|array $method The server REQUUEST_METHOD must be one of
     * these values.
     * 
     * @param bool $secure If true, the server must indicate an HTTPS request.
     * 
     * @param \Closure $is_match A custom closure to evaluate the route.
     * 
     * @param \Closure $get_path A custom closure to generate a path.
     * 
     * @param string $name_prefix A prefix for the name.
     * 
     * @param string $path_prefix A prefix for the path.
     * 
     * @return Route
     * 
     */
    public function __construct(
        $name              = null,
        $path              = null,
        $params            = null,
        $values            = null,
        $method            = null,
        $secure            = null,
        \Closure $is_match = null,
        \Closure $get_path = null,
        $name_prefix       = null,
        $path_prefix       = null
    ) {
        // set the name, with prefix if needed
        $this->name_prefix = (string) $name_prefix;
        if ($name_prefix && $name) {
            $this->name = (string) $name_prefix . $name;
        } else {
            $this->name = (string) $name;
        }
        
        // set the path, with prefix if needed
        $this->path_prefix = (string) $path_prefix;
        if ($path_prefix && $path) {
            $this->path = (string) $path_prefix . $path;
        } else {
            $this->path = (string) $path;
        }
        
        // other properties
        $this->params      = (array)  $params;
        $this->values      = (array)  $values;
        $this->method      = ($method === null) ? null : (array) $method;
        $this->secure      = ($secure === null) ? null : (bool)  $secure;
        $this->is_match    = $is_match;
        $this->get_path    = $get_path;
        
        // convert path and params to a regular expression
        $this->setRegex();
    }
    
    /**
     * 
     * Magic read-only for all properties.
     * 
     * @param string $key The property to read from.
     * 
     * @return mixed
     * 
     */
    public function __get($key)
    {
        return $this->$key;
    }
    
    /**
     * 
     * Checks if a given path and server values are a match for the 
     * route.
     * 
     * @param string $path The path to check against this Route.
     * 
     * @param array $server A copy of $_SERVER so that this Route can check 
     * against the server values.
     * 
     * @return mixed Returns this Route if it matches the path and server 
     * values, or boolean false if it does not match.
     * 
     */
    public function isMatch($path, array $server)
    {
        $is_match = preg_match("#^{$this->regex}$#", $path, $this->matches)
                 && $this->isMethodMatch($server)
                 && $this->isSecureMatch($server)
                 && $this->isCustomMatch($server);
        
        if (! $is_match) {
            return false;
        }
        
        // populate the path matches into the route values
        foreach ($this->matches as $key => $val) {
            if (is_string($key)) {
                $this->values[$key] = $val;
            }
        }
        
        // done!
        return $this;
    }
    
    /**
     * 
     * Gets the path for this Route with data replacements for param tokens.
     * 
     * @param mixed $data An array of key-value pairs to interpolate into the
     * param tokens in the path for this Route. Keys that do not map to
     * params are discarded; param tokens that have no mapped key are left in
     * place.
     * 
     * @return string
     * 
     */
    public function getPath(array $data = null)
    {
        // use a closure to modify the path data?
        if ($this->get_path) {
            $function = $this->get_path;
            $function($this, $data);
        }
        
        // interpolate into the path
        $keys = array();
        $vals = array();
        $data = array_merge($this->values, (array) $data);
        foreach ($data as $key => $val) {
            $keys[] = "{:$key}";
            $vals[] = $val;
        }
        return str_replace($keys, $vals, $this->path);
    }
    
    /**
     * 
     * Sets the regular expression for this Route based on its params.
     * 
     * @return void
     * 
     */
    protected function setRegex()
    {
        // first, extract inline token params from the path
        $find = "/\{:(.*?)(:(.*?))?\}/";
        preg_match_all($find, $this->path, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $whole = $match[0];
            $name  = $match[1];
            if (isset($match[3])) {
                // there is an inline token pattern; retain it, overriding
                // the existing param ...
                $this->params[$name] = $match[3];
                // ... and replace in the path without the pattern.
                $this->path = str_replace($whole, "{:$name}", $this->path);
            } elseif (! isset($this->params[$name])) {
                // use a default pattern when none exists
                $this->params[$name] = "([^/]+)";
            }
        }
        
        // now create the regular expression from the path and param patterns
        $this->regex = $this->path;
        if ($this->params) {
            $keys = array();
            $vals = array();
            foreach ($this->params as $name => $subpattern) {
                if ($subpattern[0] != '(') {
                    $message = "Subpattern for param '$name' must start with '('.";
                    throw new \UnexpectedValueException($message);
                } else {
                    $keys[] = "{:$name}";
                    $vals[] = "(?<$name>" . substr($subpattern, 1);
                }
            }
            $this->regex = str_replace($keys, $vals, $this->regex);
        }
    }
    
    /**
     * 
     * Checks that the Route `$method` matches the corresponding server value.
     * 
     * @param array $server A copy of $_SERVER.
     * 
     * @return bool True on a match, false if not.
     * 
     */
    protected function isMethodMatch($server)
    {
        if (isset($this->method)) {
            if (! isset($server['REQUEST_METHOD'])) {
                return false;
            }
            if (! in_array($server['REQUEST_METHOD'], $this->method)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * 
     * Checks that the Route `$secure` matches the corresponding server values.
     * 
     * @param array $server A copy of $_SERVER.
     * 
     * @return bool True on a match, false if not.
     * 
     */
    protected function isSecureMatch($server)
    {
        if ($this->secure !== null) {
            
            $is_secure = (isset($server['HTTPS']) && $server['HTTPS'] == 'on')
                      || (isset($server['SERVER_PORT']) && $server['SERVER_PORT'] = 443);
                      
            if ($this->secure == true && ! $is_secure) {
                // secure required, but not secure
                return false;
            }
            if ($this->secure == false && $is_secure) {
                // non-secure required, but is secure
                return false;
            }
        }
        return true;
    }
    
    /**
     * 
     * Checks that the custom Route `$is_match` closure returns true, given 
     * the server values.
     * 
     * @param array $server A copy of $_SERVER.
     * 
     * @return bool True on a match, false if not.
     * 
     */
    protected function isCustomMatch($server)
    {
        if (isset($this->is_match)) {
            $function = $this->is_match;
            return $function($server, $this->matches);
        }
        return true;
    }
}