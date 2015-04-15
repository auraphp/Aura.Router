<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

use Aura\Router\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 *
 * Matches against the route map.
 *
 * @package Aura.Router
 *
 */
class Matcher
{
    /**
     *
     * Logging information about which routes were attempted to match.
     *
     * @var array
     *
     */
    protected $logger;

    /**
     *
     * The map of all routes.
     *
     * @var Map
     *
     */
    protected $map;

    /**
     *
     * The Route object matched by the router.
     *
     * @var Route|false
     *
     */
    protected $matchedRoute = null;

    /**
     *
     * The first of the closest-matching failed routes.
     *
     * @var Route
     *
     */
    protected $failedRoute = null;

    /**
     *
     * Constructor.
     *
     * @param Map $map A route collection object.
     *
     * @param Generator $generator A URL path generator.
     *
     */
    public function __construct(Map $map, LoggerInterface $logger)
    {
        $this->map = $map;
        $this->logger = $logger;
    }

    /**
     *
     * Gets a route that matches a given path and other server conditions.
     *
     * @param string $path The path to match against.
     *
     * @param array $server A copy of the $_SERVER superglobal.
     *
     * @return Route|false Returns a route object when it finds a match, or
     * boolean false if there is no match.
     *
     */
    public function match(ServerRequestInterface $request)
    {
        $matchers = [
            new \Aura\Router\Matcher\Routable(),
            new \Aura\Router\Matcher\Secure(),
            new \Aura\Router\Matcher\Path(),
            new \Aura\Router\Matcher\Method(),
            new \Aura\Router\Matcher\Accept(),
            new \Aura\Router\Matcher\Server(),
        ];

        $this->failedRoute = null;
        $context = ['path' => $request->getUri()->getPath()];

        foreach ($this->map as $name => $route) {

            $context['name'] = $name;

            $match = $route->isMatch($request, $matchers);
            if ($match) {
                $this->logger->debug("{path} MATCHED ON {name}", $context);
                $this->matchedRoute = $route;
                return $route;
            }

            $betterMatch = ! $this->failedRoute
                         || $route->score > $this->failedRoute->score;
            if ($betterMatch) {
                $this->failedRoute = $route;
            }

            $context['debug'] = $route->debug;
            $this->logger->debug("{path} FAILED {debug} ON {name}", $context);
        }

        $this->matchedRoute = false;
        return false;
    }

    /**
     *
     * Get the first of the closest-matching failed routes.
     *
     * @return Route
     *
     */
    public function getFailedRoute()
    {
        return $this->failedRoute;
    }

    /**
     *
     * Returns the result of the call to match() again so you don't need to
     * run the matching process again.
     *
     * @return Route|false|null Returns null if match() has not been called
     * yet, false if it has and there was no match, or a Route object if there
     * was a match.
     *
     */
    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }
}
