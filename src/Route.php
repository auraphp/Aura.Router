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
     * The route failed to match at isMethodMatch().
     *
     * @const string
     *
     */
    const FAILED_METHOD = 'Aura\Router\Matcher\Method';

    /**
     *
     * The route failed to match at isAcceptMatch().
     *
     * @const string
     *
     */
    const FAILED_ACCEPT = 'Aura\Router\Matcher\Accept';

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
     * A Regex object for the path.
     *
     * @var Regex
     *
     */
    protected $regex;

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
     * Debugging information about why the route did not match.
     *
     * @var null|string
     *
     */
    protected $debug;

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
     * The failure code, if any, during matching.
     *
     * @var string
     *
     */
    protected $failed = null;

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
    public function isMatch(ServerRequestInterface $request)
    {
        $this->debug = null;
        $this->attributes = array();
        $this->score = 0;
        $this->failed = null;
        if ($this->isFullMatch($request)) {
            $this->setAttributes();
            return true;
        }
        return false;
    }

    /**
     *
     * Is the route a full match?
     *
     * @param string $path The path to check against this route
     *
     * @param ServerRequestInterface $request The HTTP request.
     *
     * @return bool
     *
     */
    protected function isFullMatch(ServerRequestInterface $request)
    {
        $matchers = [
            new \Aura\Router\Matcher\Routable(),
            new \Aura\Router\Matcher\Secure(),
            new \Aura\Router\Matcher\Path(),
            new \Aura\Router\Matcher\Method(),
            new \Aura\Router\Matcher\Accept(),
            new \Aura\Router\Matcher\Server(),
        ];

        foreach ($matchers as $matcher) {
            if (! $matcher($request, $this)) {
                return $this->fail(get_class($matcher));
            }
            $this->pass();
        }

        return true;
    }

    /**
     *
     * A partial match passed.
     *
     * @return bool
     *
     */
    protected function pass()
    {
        $this->score ++;
        return true;
    }

    /**
     *
     * A partial match failed.
     *
     * @param string $failed The reason of failure
     *
     * @param string $append
     *
     * @return bool
     *
     */
    protected function fail($failed, $append = null)
    {
        $this->debug = $failed . $append;
        $this->failed = $failed;
        return false;
    }

    /**
     *
     * Check whether a failure happened due to accept header
     *
     * @return bool
     *
     */
    public function failedAccept()
    {
        return $this->failed == self::FAILED_ACCEPT;
    }

    /**
     *
     * Check whether a failure happened due to http method
     *
     * @return bool
     *
     */
    public function failedMethod()
    {
        return $this->failed == self::FAILED_METHOD;
    }

    /**
     *
     * Sets the route attributes from the matched values.
     *
     * @return null
     *
     */
    protected function setAttributes()
    {
        $this->attributes = array_merge($this->defaults, $this->matches);
    }
}
