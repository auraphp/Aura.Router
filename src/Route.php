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
        if (array_key_exists($key, $this->spec)) {
            return $this->spec[$key];
        }
        
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
        return isset($this->spec[$key]) || isset($this->$key);
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
        if (! $this->spec['routable']) {
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
        
        // the data for replacements. do not use $this->merge(), as we do not
        // want to unset elements with null values.
        $data = array_merge($this->spec['values'], $data);
        
        // use a callable to modify the data?
        if ($this->spec['generate']) {
            // pass the data as an object, not as an array, so we can avoid
            // tricky hacks for references
            $arrobj = new ArrayObject($data);
            // modify
            call_user_func($this->spec['generate'], $arrobj);
            // convert back to array
            $data = $arrobj->getArrayCopy();
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
        $wildcard = $this->spec['wildcard'];
        if ($wildcard && isset($data[$wildcard])) {
            $link = rtrim($link, '/');
            foreach ($data[$wildcard] as $val) {
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
            if (! isset($this->spec['values'][$name])) {
                $this->spec['values'][$name] = null;
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
        if (! $this->spec['wildcard']) {
            return;
        }
        
        $this->regex = rtrim($this->regex, '/')
                     . "(/(?P<{$this->spec['wildcard']}>.*))?";
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
        if (isset($this->spec['tokens'][$name])) {
            return "(?P<{$name}>{$this->spec['tokens'][$name]})";
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
        foreach ($this->spec['server'] as $name => $regex) {
            
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
        if ($this->spec['secure'] !== null) {

            $is_secure = (isset($server['HTTPS']) && $server['HTTPS'] == 'on')
                      || (isset($server['SERVER_PORT']) && $server['SERVER_PORT'] == 443);

            if ($this->spec['secure'] == true && ! $is_secure) {
                $this->debug[] = 'Secure required, but not secure.';
                return false;
            }

            if ($this->spec['secure'] == false && $is_secure) {
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
        if (! $this->spec['is_match']) {
            return true;
        }

        // pass the matches as an object, not as an array, so we can avoid
        // tricky hacks for references
        $arrobj = new ArrayObject($this->matches);
        
        // attempt the match
        $result = call_user_func($this->spec['is_match'], $server, $arrobj);

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
        $this->params = $this->spec['values'];
        
        // populate the path matches into the route values. if the path match
        // is exactly an empty string, treat it as missing/unset. (this is
        // to support optional ".format" param values.)
        foreach ($this->matches as $key => $val) {
            if (is_string($key) && $val !== '') {
                $this->params[$key] = rawurldecode($val);
            }
        }

        // is a wildcard param specified?
        if ($this->spec['wildcard']) {
            $wildcard = $this->spec['wildcard'];
            // are there are actual wildcard values?
            if (empty($this->params[$wildcard])) {
                // no, set a blank array
                $this->params[$wildcard] = array();
            } else {
                // yes, retain and rawurldecode them
                $this->params[$wildcard] = array_map(
                    'rawurldecode',
                    explode('/', $this->params[$wildcard])
                );
            }
        }
    }
}
