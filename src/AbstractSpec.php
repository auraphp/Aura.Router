<?php
/**
 *
 * This file is part of the Aura for PHP.
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
     * Token names and regexes.
     *
     * @var array
     *
     */
    protected $tokens      = array();

    /**
     *
     * Server keys and regexes.
     *
     * @var array
     *
     */
    protected $server      = array();

    /**
     *
     * HTTP method(s).
     *
     * @var array
     *
     */
    protected $method      = array();

    /**
     *
     * Accept header values.
     *
     * @var array
     *
     */
    protected $accept      = array();

    /**
     *
     * Default token values.
     *
     * @var array
     *
     */
    protected $values      = array();

    /**
     *
     * Secure route?
     *
     * @var bool
     *
     */
    protected $secure      = null;

    /**
     *
     * Wildcard token name, if any.
     *
     * @var string
     *
     */
    protected $wildcard    = null;

    /**
     *
     * Routable route?
     *
     * @var bool
     *
     */
    protected $routable    = true;

    /**
     *
     * Custom callable for isMatch() logic.
     *
     * @var callable
     *
     */
    protected $is_match    = null;

    /**
     *
     * Custom callable for generate() logic.
     *
     * @var callable
     *
     */
    protected $generate    = null;

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
        $this->tokens = $tokens;
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
        $this->tokens = array_merge($this->tokens, $tokens);
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
        $this->server = $server;
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
        $this->server = array_merge($this->server, $server);
        return $this;
    }

    /**
     *
     * Sets the allowable method(s), overwriting previous the previous value.
     *
     * @param string|array $method The allowable method(s).
     *
     * @return $this
     *
     */
    public function setMethod($method)
    {
        $this->method = (array) $method;
        return $this;
    }

    /**
     *
     * Adds to the allowable method(s).
     *
     * @param string|array $method The allowable method(s).
     *
     * @return $this
     *
     */
    public function addMethod($method)
    {
        $this->method = array_merge($this->method, (array) $method);
        return $this;
    }

    /**
     *
     * Sets the list of matchable content-types, overwriting previous values.
     *
     * @param string|array $accept The matchable content-types.
     *
     * @return $this
     *
     */
    public function setAccept($accept)
    {
        $this->accept = (array) $accept;
        return $this;
    }

    /**
     *
     * Adds to the list of matchable content-types.
     *
     * @param string|array $accept The matchable content-types.
     *
     * @return $this
     *
     */
    public function addAccept($accept)
    {
        $this->accept = array_merge($this->accept, (array) $accept);
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
        $this->values = $values;
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
        $this->values = array_merge($this->values, $values);
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
     * @param bool $routable If true, this route can be matched; if not, it
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
}
