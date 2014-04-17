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
 * @property-read string $name The route name.
 * 
 * @property-read string $path The route path.
 * 
 * @property-read array $values Default values for params.
 * 
 * @property-read array $params The matched params.
 * 
 * @property-read string $regex The regular expression for the route.
 * 
 * @property-read string $matches All params found during `isMatch()`.
 * 
 * @property-read array $debug Debugging messages.
 * 
 * @property-read callable $generate A callable for generating a link.
 * 
 * @property-read string $wildcard The name of the wildcard param.
 * 
 */
class Route extends AbstractSpec
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
     * Matched param values.
     * 
     * @var array
     * 
     */
    protected $params = array();

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
     * All params found during the `isMatch()` process, both from the path
     * tokens and from matched server values.
     * 
     * @var array
     * 
     * @see isMatch()
     * 
     */
    protected $matches = array();

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
     * Magic read-only for all properties and spec keys.
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
        $this->debug = array();
        $this->params = array();
        
        if (! $this->routable) {
            $this->debug[] = 'Not routable.';
            return false;
        }
        
        $this->setRegex();

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
     * Sets the regular expression for this Route.
     * 
     * @return null
     * 
     */
    protected function setRegex()
    {
        if ($this->regex) {
            return;
        }
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
        
        // the list of all tokens
        $list = explode(',', $matches[1]);
        
        // the subpattern parts
        $head = '';
        $tail = '';
        
        // if the optional set is the first part of the path. make sure there
        // is a leading slash in the replacement before the optional param.
        if (substr($this->regex, 0, 2) == '{/') {
            $name = array_shift($list);
            $head = "/({{$name}})?";
        }
        
        // add remaining optional params
        foreach ($list as $name) {
            $head .= "(/{{$name}}";
            $tail .= ')?';
        }
        
        // put together the regex replacement
        $this->regex = str_replace($matches[0], $head . $tail, $this->regex);
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
            if (! isset($this->values[$name])) {
                $this->values[$name] = null;
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
     * Checks that $_SERVER values match their related regular expressions.
     * 
     * @param array $server A copy of $_SERVER.
     * 
     * @return bool True if they all match, false if not.
     * 
     */
    protected function isServerMatch($server)
    {
        foreach ($this->server as $name => $regex) {
            
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
        if ($this->secure === null) {
            return true;
        }

        if ($this->secure != $this->serverIsSecure($server)) {
            $this->debug[] = 'Not a secure match.';
            return false;
        }

        return true;
    }

    protected function serverIsSecure($server)
    {
        return (isset($server['HTTPS']) && $server['HTTPS'] == 'on')
            || (isset($server['SERVER_PORT']) && $server['SERVER_PORT'] == 443);
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
        $arrobj = new ArrayObject($this->matches);
        
        // attempt the match
        $result = call_user_func($this->is_match, $server, $arrobj);

        // convert back to array
        $this->matches = $arrobj->getArrayCopy();

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
        $this->params = $this->values;
        $this->setParamsWithMatches();
        $this->setParamsWithWildcard();

    }

    protected function setParamsWithMatches()
    {
        // populate the path matches into the route values. if the path match
        // is exactly an empty string, treat it as missing/unset. (this is
        // to support optional ".format" param values.)
        foreach ($this->matches as $key => $val) {
            if (is_string($key) && $val !== '') {
                $this->params[$key] = rawurldecode($val);
            }
        }
    }

    protected function setParamsWithWildcard()
    {
        if (! $this->wildcard) {
            return;
        }

        if (empty($this->params[$this->wildcard])) {
            $this->params[$this->wildcard] = array();
            return;
        }

        $this->params[$this->wildcard] = array_map(
            'rawurldecode',
            explode('/', $this->params[$this->wildcard])
        );
    }
}
