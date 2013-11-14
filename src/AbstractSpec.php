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

/**
 * 
 * An abstract specification for defining a route.
 * 
 * @package Aura.Router
 * 
 */
class AbstractSpec
{
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
	);
	
    /**
     * 
     * Sets the regular expressions for param tokens.
     * 
     * @param array $tokens The regular expressions for param tokens.
     * 
     * @return $this
     * 
     */
    public function setTokens(array $tokens)
    {
        $this->spec['tokens'] = $tokens;
        return $this;
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
        $this->spec['tokens'] = array_merge($this->spec['tokens'], $tokens);
        $this->regex = null;
        return $this;
    }
    
    /**
     * 
     * Sets the regular expressions for server values.
     * 
     * @param array $server The regular expressions for server values.
     * 
     * @return $this
     * 
     */
    public function setServer(array $server)
    {
        $this->spec['server'] = $server;
        return $this;
    }
    
    /**
     * 
     * Merges with the existing regular expressions for server values.
     * 
     * @param array $server Regular expressions for server values.
     * 
     * @return $this
     * 
     */
    public function addServer(array $server)
    {
        $this->spec['server'] = array_merge($this->spec['server'], $server);
        $this->regex = null;
        return $this;
    }
    
    /**
     * 
     * Sets the default values for params.
     * 
     * @param array $values Default values for params.
     * 
     * @return $this
     * 
     */
    public function setValues(array $values)
    {
        $this->spec['values'] = $values;
        return $this;
    }
    
    /**
     * 
     * Merges with the existing default values for params.
     * 
     * @param array $values Default values for params.
     * 
     * @return $this
     * 
     */
    public function addValues(array $values)
    {
        $this->spec['values'] = array_merge($this->spec['values'], $values);
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
        $this->spec['secure'] = ($secure === null) ? null : (bool) $secure;
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
        $this->spec['wildcard'] = $wildcard;
        return $this;
    }
    
    /**
     * 
     * Sets whether or not this route should be used for matching.
     * 
     * @param bool $routable If true, this route can be matched; if not, it
     * can be used only to generate a path.
     * 
     * @return $this
     * 
     */
    public function setRoutable($routable = true)
    {
        $this->spec['routable'] = (bool) $routable;
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
        $this->spec['is_match'] = $is_match;
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
        $this->spec['generate'] = $generate;
        return $this;
    }
}
