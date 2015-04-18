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
 * @package Aura.Router
 *
 * @property-read string $name The route name.
 *
 * @property-read string $path The route path.
 *
 * @property-read string $namePrefix
 *
 * @property-read string $pathPrefix
 *
 * @property-read string $host
 *
 * @property-read array $defaults Default values for attributes.
 *
 * @property-read array $attributes Attribute values added by the rules.
 *
 * @property-read array $tokens Plceholder token names and regexes.
 *
 * @property-read string $wildcard The name of the wildcard token.
 *
 * @property-read array $accept
 *
 * @property-read array $extras
 *
 * @property-read bool $secure
 *
 * @property-read array $methods
 *
 * @property-read bool $routable
 *
 * @property-read string $failedRule
 *
 */
class Route
{
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
     * Attribute values added by the rules.
     *
     * @var array
     *
     */
    protected $attributes = [];

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
     * Extra key-value pairs to attach to the route; intended for use by
     * custom matching rules.
     *
     * @var array
     *
     */
    protected $extras = [];

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
     * The action, controller, callable, closure, etc. this route points to.
     *
     * @var mixed
     *
     */
    protected $handler;

    /**
     *
     * The host string this route responds to.
     *
     * @var string
     *
     */
    protected $host;

    /**
     *
     * HTTP method(s) this route responds to.
     *
     * @var array
     *
     */
    protected $methods = array();

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
     * Prefix the route name with this string.
     *
     * @var string
     *
     */
    protected $namePrefix;

    /**
     *
     * The path for this Route.
     *
     * @var string
     *
     */
    protected $path;

    /**
     *
     * Prefix the route path with this string.
     *
     * @var string
     *
     */
    protected $pathPrefix;

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
     * Secure route?
     *
     * @var bool
     *
     */
    protected $secure = null;

    /**
     *
     * Placeholder token names and regexes.
     *
     * @var array
     *
     */
    protected $tokens = array();

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
     * When cloning the Route, reset the `$attributes` to an empty array, and
     * clear the `$failedRule`.
     *
     */
    public function __clone()
    {
        // $this is the cloned instance, not the original
        $this->attributes = $this->defaults;
        $this->failedRule = null;
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
     * Invoke the `$handler` property with arbitrary arguments.
     *
     * @param array ...$args Arbitrary arguments.
     *
     * @return mixed
     *
     */
    public function __invoke(...$args)
    {
        $handler = $this->handler;
        return $handler(...$args);
    }

    /**
     *
     * The route only responds to these content types.
     *
     * @param string|array $accept The content-types.
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
     * Merges with the existing extra key-value pairs; this merge is recursive,
     * so the values can be arbitrarily deep.
     *
     * @param array $extras The extra key-value pairs.
     *
     * @return $this
     *
     */
    public function extras(array $extras)
    {
        $this->extras = array_merge_recursive($this->extras, $extras);
        return $this;
    }

    public function failedRule($failedRule)
    {
        $this->failedRule = $failedRule;
        return $this;
    }

    /**
     *
     * The route leads to this handler.
     *
     * @param mixed $handler The handler for this route.
     *
     * @return $this
     *
     */
    public function handler($handler)
    {
        $this->handler = $handler;
        return $this;
    }

    public function host($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     *
     * Merges with the existing method(s).
     *
     * @param string|array $method The HTTP method(s).
     *
     * @return $this
     *
     */
    public function methods($method)
    {
        $this->methods = array_merge($this->methods, (array) $method);
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
     * Appends to the existing name prefix.
     *
     * @param string $namePrefix The name prefix to append.
     *
     * @return $this
     *
     * @throws Exception\ImmutableProperty when the name has already been set.
     *
     */
    public function namePrefix($namePrefix)
    {
        if ($this->name !== null) {
            $message = __CLASS__ . '::$namePrefix is immutable once $name is set';
            throw new Exception\ImmutableProperty($message);
        }
        $this->namePrefix = $namePrefix;
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

    /**
     *
     * Appends to the existing path prefix.
     *
     * @param string $pathPrefix The path prefix to append.
     *
     * @return $this
     *
     * @throws Exception\ImmutableProperty when the path has already been set.
     *
     */
    public function pathPrefix($pathPrefix)
    {
        if ($this->path !== null) {
            $message = __CLASS__ . '::$pathPrefix is immutable once $path is set';
            throw new Exception\ImmutableProperty($message);
        }
        $this->pathPrefix = $pathPrefix;
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
     * Merges with the existing tokens.
     *
     * @param array $tokens The tokens.
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
}
