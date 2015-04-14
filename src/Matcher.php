<?php
/**
 *
 * This file is part of the Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

use Aura\Router\Exception;

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
    protected $debug = array();

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
    protected $matched_route = null;

    /**
     *
     * The first of the closest-matching failed routes.
     *
     * @var Route
     *
     */
    protected $failed_route = null;

    /**
     *
     * Constructor.
     *
     * @param Map $map A route collection object.
     *
     * @param Generator $generator A URL path generator.
     *
     */
    public function __construct(Map $map)
    {
        $this->map = $map;
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
    public function match($path, array $server = array())
    {
        $this->debug = array();
        $this->failed_route = null;

        foreach ($this->map as $route) {

            $this->debug[] = $route;

            $match = $route->isMatch($path, $server);
            if ($match) {
                $this->matched_route = $route;
                return $route;
            }

            $better_match = ! $this->failed_route
                         || $route->score > $this->failed_route->score;
            if ($better_match) {
                $this->failed_route = $route;
            }
        }

        $this->matched_route = false;
        return false;
    }

    /**
     *
     * Gets the attempted route matches.
     *
     * @return array An array of routes from the last match() attempt.
     *
     */
    public function getDebug()
    {
        return $this->debug;
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
        return $this->failed_route;
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
        return $this->matched_route;
    }
}
