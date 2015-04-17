<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

use Psr\Http\Message\ServerRequestInterface;

/**
 *
 * An individual route with a name, path, attributes, defaults, etc.
 *
 * In general, you should never need to instantiate a Route directly. Use the
 * Map instead.
 *
 * @package Aura.Router
 *
 * @property-read string $name The route name.
 *
 * @property-read string $path The route path.
 *
 * @property-read array $defaults Default values for attributes.
 *
 * @property-read array $attributes Attribute values added by the rules.
 *
 * @property-read array $tokens The regular expression for the route.
 *
 * @property-read string $wildcard The name of the wildcard attribute.
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
     * The path for this Route with attribute tokens.
     *
     * @var string
     *
     */
    protected $path;

    /**
     *
     * Token names and regexes.
     *
     * @var array
     *
     */
    protected $tokens = array();

    /**
     *
     * HTTP method(s).
     *
     * @var array
     *
     */
    protected $methods = array();

    /**
     *
     * Accept these content-types.
     *
     * @var array
     *
     */
    protected $accept = array();

    /**
     *
     * Default attribute values.
     *
     * @var array
     *
     */
    protected $defaults = array();

    /**
     *
     * Secure route?
     *
     * @var bool
     *
     */
    protected $secure = null;

    /**
     *
     * Wildcard token name, if any.
     *
     * @var string
     *
     */
    protected $wildcard = null;

    /**
     *
     * Routable route?
     *
     * @var bool
     *
     */
    protected $routable = true;

    /**
     *
     * Attribute values added by the rules.
     *
     * @var array
     *
     */
    protected $attributes = [];

    /**
     *
     * The rule that failed, if any, during matching.
     *
     * @var string
     *
     */
    protected $failedRule;

    /**
     *
     * A prefix to add to the name.
     *
     * @var string
     *
     */
    protected $namePrefix;

    /**
     *
     * A prefix to add to the path.
     *
     * @var string
     *
     */
    protected $pathPrefix;

    protected $extras = [];

    protected $host;

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

    public function __clone()
    {
        // $this is the cloned instance, not the original
        $this->attributes = $this->defaults;
        $this->failedRule = null;
    }

    public function appendPathPrefix($pathPrefix)
    {
        if ($this->path !== null) {
            $message = __CLASS__ . '::$pathPrefix is immutable once $path is set';
            throw new Exception\ImmutableProperty($message);
        }
        $this->pathPrefix .= $pathPrefix;
        return $this;
    }

    public function appendNamePrefix($namePrefix)
    {
        if ($this->name !== null) {
            $message = __CLASS__ . '::$namePrefix is immutable once $name is set';
            throw new Exception\ImmutableProperty($message);
        }
        $this->namePrefix .= $namePrefix;
        return $this;
    }

    public function path($path)
    {
        if ($this->path !== null) {
            $message = __CLASS__ . '::$path is immutable once set';
            throw new Exception\ImmutableProperty($message);
        }
        $this->path = $this->pathPrefix . $path;
        return $this;
    }

    public function name($name)
    {
        if ($this->name !== null) {
            $message = __CLASS__ . '::$name is immutable once set';
            throw new Exception\ImmutableProperty($message);
        }
        $this->name = $this->namePrefix . $name;
        return $this;
    }

    /**
     *
     * Merges with the existing regular expressions for attribute tokens.
     *
     * @param array $tokens Regular expressions for attribute tokens.
     *
     * @return $this
     *
     */
    public function tokens(array $tokens)
    {
        $this->tokens = array_merge($this->tokens, $tokens);
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
    public function methods($method)
    {
        $this->methods = array_merge($this->methods, (array) $method);
        return $this;
    }

    /**
     *
     * Sets the list of matchable content-types.
     *
     * @param string|array $accept The matchable content-types.
     *
     * @return $this
     *
     */
    public function accept($accept)
    {
        $this->accept = (array) $accept;
        return $this;
    }

    /**
     *
     * Merges with the existing default values for attributes.
     *
     * @param array $defaults Default values for attributes.
     *
     * @return $this
     *
     */
    public function defaults(array $defaults)
    {
        $this->defaults = array_merge($this->defaults, $defaults);
        return $this;
    }

    /**
     *
     * Merges with the existing extra keys and values; this merge is recursive,
     * so the values can arbitrarily deep.
     *
     * @param array $extras The extra keys and values.
     *
     * @return $this
     *
     */
    public function extras(array $extras)
    {
        $this->extras = array_merge_recursive($this->extras, $extras);
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
    public function secure($secure = true)
    {
        $this->secure = ($secure === null) ? null : (bool) $secure;
        return $this;
    }

    /**
     *
     * Sets the name of the wildcard token, if any.
     *
     * @param string $wildcard The name of the wildcard token, if any.
     *
     * @return $this
     *
     */
    public function wildcard($wildcard)
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
    public function routable($routable = true)
    {
        $this->routable = (bool) $routable;
        return $this;
    }

    public function host($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     *
     * Adds attributes to the Route.
     *
     * @param array $attributes The attributes to add.
     *
     * @return null
     *
     */
    public function attributes(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    public function failedRule($failedRule)
    {
        $this->failedRule = $failedRule;
        return $this;
    }
}
