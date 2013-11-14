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

use ArrayObject;
use Closure;

/**
 * 
 * Represents an individual route with a name, path, params, values, etc.
 *
 * In general, you should never need to instantiate a Route directly. Use the
 * RouteFactory instead, or the Router.
 * 
 * @package Aura.Router
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
     * A map of params to regex subpatterns; all-caps param names are treated
     * as `$_SERVER` keys to be checked.
     * 
     * @var array
     * 
     */
    protected $tokens = array();

    /**
     * 
     * Defalt param values.
     * 
     * @var array
     * 
     */
    protected $default = array();
    
    /**
     * 
     * Matched param values.
     * 
     * @var array
     * 
     */
    protected $params = array();

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
     * A callable to provide custom matching logic against the 
     * server values and matched params from this Route. The signature must be 
     * `function(array $server, \ArrayObject $matches)` and must return a 
     * boolean: true to accept this Route match, or false to deny the match. 
     * Note that this allows a wide range of manipulations, and further allows 
     * the developer to modify the matched params as needed.
     * 
     * @var callable
     * 
     * @see isMatch()
     * 
     */
    protected $is_match;

    /**
     * 
     * A callable to modify path-generation values. The signature must be
     * `function (\Aura\Router\Route $route, array $data)`. Its return value
     * is an array  of data to be used in the path. The `$route` is this Route
     * object, and  `$data` is the set of key-value pairs to be interpolated
     * into the path as provided by the caller.
     * 
     * @var callable
     * 
     * @see generate()
     * 
     */
    protected $generate;

    /**
     * 
     * If routable, this route should be used in matching.  If not, it should
     * be used only to generate a path.
     * 
     * @var bool
     * 
     */
    protected $routable = true;

    /**
     * 
     * The `$path` property converted to a regular expression, using the
     * `$tokens` subpatterns.
     * 
     * @var string
     * 
     */
    protected $regex;

    /**
     * 
     * All param matches found in the path during the `isMatch()` process,
     * both in the path and in `$_SERVER`.
     * 
     * @var array
     * 
     * @see isMatch()
     * 
     */
    protected $matches;

    /**
     * 
     * Debugging information about why the route did not match.
     * 
     * @var array
     * 
     */
    protected $debug;

    /**
     * 
     * The name of the wildcard param, if any.
     * 
     * @var array
     * 
     */
    protected $wildcard;
    
    /**
     * 
     * Constructor.
     * 
     * @param string $path The path for this Route with param token
     * placeholders.
     * 
     * @param string $name The name for this route.
     * 
     */
    public function __construct($path, $name = null)
    {
        $this->path = $path;
        $this->name = $name;
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
     * Magic isset() for all properties.
     * 
     * @param string $key The property to check if isset().
     * 
     * @return bool
     * 
     */
    public function __isset($key)
    {
        return isset($this->$key);
    }

    /**
     * 
     * Sets the regular expressions for param tokens, replacing all
     * previous values.
     * 
     * @param array $tokens Regular expressions for param tokens.
     * 
     * @return $this
     * 
     */
    public function setTokens(array $tokens)
    {
        $this->tokens = array();
        return $this->addTokens($tokens);
    }
    
    /**
     * 
     * Merges with the existing regular expressions for param tokens.
     * 
     * @param array $tokens Regular expressions for param tokens.
     * 
     * @return $this
     * 
     */
    public function addTokens(array $tokens)
    {
        $this->tokens = array_merge($this->tokens, $tokens);
        $this->regex = null;
        return $this;
    }
    
    /**
     * 
     * Sets the default values for params, replacing all previous values.
     * 
     * @param array $default Default values for params.
     * 
     * @return $this
     * 
     */
    public function setDefault(array $default)
    {
        $this->default = array();
        return $this->addDefault($default);
    }
    
    /**
     * 
     * Merges with the existing default values for params.
     * 
     * @param array $default Default values for params.
     * 
     * @return $this
     * 
     */
    public function addDefault(array $default)
    {
        $this->default = array_merge($this->default, $default);
        return $this;
    }
    
    /**
     * 
     * Sets whether or not the route must be secure.
     * 
     * @param bool $secure If true, the server must indicate an HTTPS request;
     * if false, it must *not* be HTTPS; if null, it doesn't matter.
     * 
     * @return $this
     * 
     */
    public function setSecure($secure = true)
    {
        $this->secure = ($secure === null) ? null : (bool) $secure;
        return $this;
    }
    
    /**
     * 
     * Sets the name of the wildcard param.
     * 
     * @param string $wildcard The name of the wildcard param, if any.
     * 
     * @return $this
     * 
     */
    public function setWildcard($wildcard)
    {
        $this->wildcard = $wildcard;
        return $this;
    }
    
    /**
     * 
     * Sets whether or not this route should be used for matching.
     * 
     * @param bool $routable If true, this Route can be matched; if not, it
     * can be used only to generate a path.
     * 
     * @return $this
     * 
     */
    public function setRoutable($routable = true)
    {
        $this->routable = (bool) $routable;
        return $this;
    }
    
    /**
     * 
     * Sets a custom callable to evaluate the route for matching.
     * 
     * @param callable $is_match A custom callable to evaluate the route.
     * 
     * @return $this
     * 
     */
    public function setIsMatchCallable($is_match)
    {
        $this->is_match = $is_match;
        return $this;
    }
    
    /**
     * 
     * Sets a custom callable to modify data for `generate()`.
     * 
     * @param callable $generate A custom callable to modify data for
     * `generate()`.
     * 
     * @return $this
     * 
     */
    public function setGenerateCallable($generate)
    {
        $this->generate = $generate;
        return $this;
    }
    
    /**
     * 
     * Checks if a given path and server values are a match for this
     * Route.
     * 
     * @param string $path The path to check against this Route.
     * 
     * @param array $server A copy of $_SERVER so that this Route can check 
     * against the server values.
     * 
     * @return bool
     * 
     */
    public function isMatch($path, array $server)
    {
        // reset
        $this->debug = array();
        $this->params = array();
        
        // routable?
        if (! $this->routable) {
            $this->debug[] = 'Not routable.';
            return false;
        }
        
        // do we have a regex?
        if (! $this->regex) {
            $this->setRegex();
        }
        
        // check matches
        $is_match = $this->isRegexMatch($path)
                 && $this->isServerMatch($server)
                 && $this->isSecureMatch($server)
                 && $this->isCustomMatch($server);
        if (! $is_match) {
            return false;
        }
        
        // set params from matches, and done!
        $this->setParams();
        return true;
    }

    /**
     * 
     * Gets the path for this Route with data replacements for param tokens.
     * 
     * @param array $data An array of key-value pairs to interpolate into the
     * param tokens in the path for this Route. Keys that do not map to
     * params are discarded; param tokens that have no mapped key are left in
     * place.
     * 
     * @return string
     * 
     */
    public function generate(array $data = array())
    {
        // the base link template
        $link = $this->path;
        
        // the data for replacements
        $data = array_merge($this->default, $data);
        
        // use a callable to modify the data?
        if ($this->generate) {
            $data = call_user_func($this->generate, $this, (array) $data);
        }
        
        // replacements for single tokens
        $repl = array();
        foreach ($data as $key => $val) {
            // encode the single value
            if (is_scalar($val) || $val === null) {
                $repl["{{$key}}"] = rawurlencode($val);
            }
        }
        
        // replacements for optional params, if any
        preg_match('#{/([a-z][a-zA-Z0-9_,]*)}#', $link, $matches);
        if ($matches) {
            // this is the full token to replace in the link
            $key = $matches[0];
            // start with an empty replacement
            $repl[$key] = '';
            // the optional param names in the token
            $names = explode(',', $matches[1]);
            // look for data for each of the param names
            foreach ($names as $name) {
                // is there data for this optional param?
                if (! isset($data[$name])) {
                    // options are *sequentially* optional, so if one is
                    // missing, we're done
                    break;
                }
                // encode the optional value
                if (is_scalar($data[$name])) {
                    $repl[$key] .= '/' . rawurlencode($data[$name]);
                }
            }
        }
        
        // replace params in the link, including optional params
        $link = strtr($link, $repl);
        
        // add wildcard data
        if ($this->wildcard && isset($data[$this->wildcard])) {
            $link = rtrim($link, '/');
            foreach ($data[$this->wildcard] as $val) {
                // encode the wildcard value
                if (is_scalar($val)) {
                    $link .= '/' . rawurlencode($val);
                }
            }
        }
        
        // done!
        return $link;
    }

    /**
     * 
     * Sets the regular expression for this Route.
     * 
     * @return null
     * 
     */
    protected function setRegex()
    {
        $this->regex = $this->path;
        $this->setRegexOptionalParams();
        $this->setRegexParams();
        $this->setRegexWildcard();
        $this->regex = '^' . $this->regex . '$';
    }

    /**
     * 
     * Expands optional params in the regex from ``{/foo,bar,baz}` to
     * `(/{foo}(/{bar}(/{baz})?)?)?`.
     * 
     * @return null
     * 
     */
    protected function setRegexOptionalParams()
    {
        preg_match('#{/([a-z][a-zA-Z0-9_,]*)}#', $this->regex, $matches);
        if (! $matches) {
            return;
        }
        
        $list = explode(',', $matches[1]);
        $head = '';
        $tail = '';
        foreach ($list as $name) {
            $head .= "(/{{$name}}";
            $tail .= ')?';
        }
        $repl = $head . $tail;
        $this->regex = str_replace($matches[0], $repl, $this->regex);
    }
    
    /**
     * 
     * Expands param names in the regex to named subpatterns.
     * 
     * @return null
     * 
     */
    protected function setRegexParams()
    {
        $find = '#{([a-z][a-zA-Z0-9_]*)}#';
        preg_match_all($find, $this->regex, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $name = $match[1];
            $subpattern = $this->getSubpattern($name);
            $this->regex = str_replace("{{$name}}", $subpattern, $this->regex);
            if (! array_key_exists($name, $this->default)) {
                $this->default[$name] = null;
            }
        }
    }
    
    /**
     * 
     * Adds a wildcard subpattern to the end of the regex.
     * 
     * @return null
     * 
     */
    protected function setRegexWildcard()
    {
        if (! $this->wildcard) {
            return;
        }
        
        $this->regex = rtrim($this->regex, '/')
                     . "(/(?P<{$this->wildcard}>.*))?";
    }
    
    /**
     * 
     * Returns a named subpattern for a param name.
     * 
     * @param string $name The param name.
     * 
     * @return string The named subpattern.
     * 
     */
    protected function getSubpattern($name)
    {
        // is there a custom subpattern for the name?
        if (isset($this->tokens[$name])) {
            return "(?P<{$name}>{$this->tokens[$name]})";
        }
        
        // use a default subpattern
        return "(?P<{$name}>[^/]+)";
    }
    
    /**
     * 
     * Checks that the path matches the Route regex.
     * 
     * @param string $path The path to match against.
     * 
     * @return bool True on a match, false if not.
     * 
     */
    protected function isRegexMatch($path)
    {
        $regex = "#^{$this->regex}$#";
        $match = preg_match($regex, $path, $this->matches);
        if (! $match) {
            $this->debug[] = 'Not a regex match.';
        }
        return $match;
    }

    /**
     * 
     * Checks that $_SERVER values match requirements.
     * 
     * @param array $server A copy of $_SERVER.
     * 
     * @return bool True if, false if not.
     * 
     */
    protected function isServerMatch($server)
    {
        foreach ($this->tokens as $name => $regex) {
            
            // only honor all caps as $_SERVER keys
            if ($name !== strtoupper($name)) {
                continue;
            }
            
            // get the corresponding server value
            $value = isset($server[$name]) ? $server[$name] : '';
            
            // define the regex for that server value
            $regex = "#(?P<{$name}>{$regex})#";
            
            // does the server value match the required regex?
            $match = preg_match($regex, $value, $matches);
            if (! $match) {
                $this->debug[] = "Not a server match ($name).";
                return false;
            }
            
            // retain the matched portion, not the entire server value
            $this->matches[$name] = $matches[$name];
        }
        
        // everything matched!
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
                      || (isset($server['SERVER_PORT']) && $server['SERVER_PORT'] == 443);

            if ($this->secure == true && ! $is_secure) {
                $this->debug[] = 'Secure required, but not secure.';
                return false;
            }

            if ($this->secure == false && $is_secure) {
                $this->debug[] = 'Non-secure required, but is secure.';
                return false;
            }
        }
        return true;
    }

    /**
     * 
     * Checks that the custom Route `$is_match` callable returns true, given 
     * the server values.
     * 
     * @param array $server A copy of $_SERVER.
     * 
     * @return bool True on a match, false if not.
     * 
     */
    protected function isCustomMatch($server)
    {
        if (! $this->is_match) {
            return true;
        }

        // pass the matches as an object, not as an array, so we can avoid
        // tricky hacks for references
        $matches = new ArrayObject($this->matches);
        $result = call_user_func($this->is_match, $server, $matches);

        // convert back to array
        $this->matches = $matches->getArrayCopy();

        // did it match?
        if (! $result) {
            $this->debug[] = 'Not a custom match.';
        }

        return $result;
    }
    
    /**
     * 
     * Sets the route params from the matched values.
     * 
     * @return null
     * 
     */
    protected function setParams()
    {
        $this->params = $this->default;
        
        // populate the path matches into the route values. if the path match
        // is exactly an empty string, treat it as missing/unset. (this is
        // to support optional ".format" param values.)
        foreach ($this->matches as $key => $val) {
            if (is_string($key) && $val !== '') {
                $this->params[$key] = rawurldecode($val);
            }
        }

        // is a wildcard param specified?
        if ($this->wildcard) {
            // are there are actual wildcard values?
            if (empty($this->params[$this->wildcard])) {
                // no, set a blank array
                $this->params[$this->wildcard] = array();
            } else {
                // yes, retain and rawurldecode them
                $this->params[$this->wildcard] = array_map(
                    'rawurldecode',
                    explode('/', $this->params[$this->wildcard])
                );
            }
        }
    }
}
