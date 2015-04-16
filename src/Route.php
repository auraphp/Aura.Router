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
 * @property-read array $attributes The matched attributes.
 *
 * @property-read Regex $regex The regular expression for the route.
 *
 * @property-read array $tokens The regular expression for the route.
 *
 * @property-read array $matches All attributes found during `isMatch()`.
 *
 * @property-read string $debug Debugging messages.
 *
 * @property-read string $wildcard The name of the wildcard attribute.
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
     * The path for this Route with attribute tokens.
     *
     * @var string
     *
     */
    protected $path;

    /**
     *
     * Matched attribute values.
     *
     * @var array
     *
     */
    protected $attributes = array();

    /**
     *
     * All attributes found during the `isMatch()` process, both from the path
     * tokens and from matched server values.
     *
     * @var array
     *
     * @see isMatch()
     *
     */
    protected $matches = [];

    /**
     *
     * The matching score for this route (+1 for each is*Match() that passes).
     *
     * @var int
     *
     */
    protected $score = 0;

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
     * Constructor.
     *
     * @param string $path The path for this Route with attribute token
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

    public function addMatches(array $matches)
    {
        $this->matches = array_merge($this->matches, $matches);
    }

    /**
     *
     * Checks if a given path and server values are a match for this
     * Route.
     *
     * @param string $path The path to check against this Route.
     *
     * @param ServerRequestInterface $request The HTTP request.
     *
     * @return bool
     *
     */
    public function isMatch(ServerRequestInterface $request, array $rules)
    {
        $this->matches = array();
        $this->attributes = array();
        $this->score = 0;
        $this->failedRule = null;

        foreach ($rules as $rule) {
            if (! $rule($request, $this)) {
                $this->failedRule = get_class($rule);
                return false;
            }
            $this->score ++;
        }

        $this->attributes = array_merge($this->defaults, $this->matches);
        return true;
    }

    /**
     *
     * Get the rule that failed, if any.
     *
     * @return bool
     *
     */
    public function getFailedRule()
    {
        return $this->failedRule;
    }
}
