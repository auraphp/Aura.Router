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

    protected $rules = array();

    /**
     *
     * The Route object matched by the router.
     *
     * @var Route|false
     *
     */
    protected $matchedRoute;

    /**
     *
     * The first of the closest-matching failed routes.
     *
     * @var Route
     *
     */
    protected $failedRoute;

    protected $failedScore = 0;

    /**
     *
     * Constructor.
     *
     * @param Map $map A route collection object.
     *
     * @param Generator $generator A URL path generator.
     *
     */
    public function __construct(Map $map, LoggerInterface $logger, array $rules)
    {
        $this->map = $map;
        $this->logger = $logger;
        $this->rules = $rules;
    }

    public function match(ServerRequestInterface &$request)
    {
        $route = $this->getRouteForRequest($request);
        if (! $route) {
            return false;
        }

        foreach ($route->attributes as $key => $val) {
            $request = $request->withAttribute($key, $val);
        }

        return $route;
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
    public function getRouteForRequest(ServerRequestInterface &$request)
    {
        $this->matchedRoute = false;
        $this->failedRoute = null;
        $this->failedScore = 0;
        $path = $request->getUri()->getPath();

        foreach ($this->map as $name => $proto) {
            $route = $this->matchRoute($request, $proto, $name, $path);
            if ($route) {
                return $route;
            }
        }

        return false;
    }

    protected function matchRoute($request, $proto, $name, $path)
    {
        if (! $proto->isRoutable) {
            continue;
        }
        $route = clone $proto;
        return $this->applyRules($request, $route, $name, $path);
    }

    protected function applyRules($request, $route, $name, $path)
    {
        $score = 0;
        foreach ($this->rules as $rule) {
            if (! $rule($request, $route)) {
                return $this->ruleFailed($request, $route, $name, $path, $rule, $score);
            }
            $score ++;
        }
        return $this->routeMatched($route, $name, $path);
    }

    protected function ruleFailed($request, $route, $name, $path, $rule, $score)
    {
        $ruleClass = get_class($rule);
        $route->failedRule($ruleClass);

        if (! $this->failedRoute || $score > $this->failedScore) {
            $this->failedRoute = $route;
            $this->failedScore = $score;
        }

        $this->logger->debug("{path} FAILED {ruleClass} ON {name}", [
            'path' => $path,
            'ruleClass' => $ruleClass,
            'name' => $name
        ]);

        return false;
    }

    protected function routeMatched($route, $name, $path)
    {
        $this->logger->debug("{path} MATCHED ON {name}", [
            'path' => $path,
            'name' => $name,
        ]);
        $this->matchedRoute = $route;
        return $route;
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
