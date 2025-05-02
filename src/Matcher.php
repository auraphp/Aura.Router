<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

use Aura\Router\Rule\RuleIterator;
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
     * @var LoggerInterface
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
     * A collection of matching rules to iterate through.
     *
     * @var RuleIterator
     *
     */
    protected $ruleIterator;

    /**
     *
     * The Route object matched by the router.
     *
     * @var Route|false|null
     *
     */
    protected $matchedRoute;

    /**
     *
     * The first of the closest-matching failed routes.
     *
     * @var Route|null
     *
     */
    protected $failedRoute;

    /**
     *
     * The score of the closest-matching failed route.
     *
     * @var int
     *
     */
    protected $failedScore = 0;

    /**
     *
     * Constructor.
     *
     * @param Map $map A route collection object.
     *
     * @param LoggerInterface $logger A logger object.
     *
     * @param RuleIterator $ruleIterator A collection of matching rules.
     *
     */
    public function __construct(
        Map $map,
        LoggerInterface $logger,
        RuleIterator $ruleIterator
    ) {
        $this->map = $map;
        $this->logger = $logger;
        $this->ruleIterator = $ruleIterator;
    }

    /****
     * Attempts to find and return the first route that matches the given HTTP request.
     *
     * Filters candidate routes based on the request path, then applies matching rules to each candidate until a match is found. Returns the matched route or false if no route matches.
     *
     * @param ServerRequestInterface $request The HTTP request to match against available routes.
     * @return Route|false The matched route object, or false if no route matches the request.
     */
    public function match(ServerRequestInterface $request)
    {
        $this->matchedRoute = false;
        $this->failedRoute = null;
        $this->failedScore = 0;
        $path = $request->getUri()->getPath();

        $possibleRoutes = $this->getMatchedTree($path);
        foreach ($possibleRoutes as $proto) {
            if (is_array($proto)) {
                continue;
            }
            $route = $this->requestRoute($request, $proto, $path);
            if ($route) {
                return $route;
            }
        }

        return false;
    }

    /****
     * Attempts to match a proto-route to the given request and path.
     *
     * Clones the provided proto-route and applies matching rules to determine if it matches the request and path.
     *
     * @param ServerRequestInterface $request The HTTP request to match.
     * @param Route $proto The proto-route candidate.
     * @param string $path The request path.
     * @return Route|false The matched Route on success, or false if the proto-route is not routable or does not match.
     */
    protected function requestRoute($request, $proto, $path)
    {
        if (! $proto->isRoutable) {
            return false;
        }
        $route = clone $proto;
        return $this->applyRules($request, $route, $route->name, $path);
    }

    /**
     *
     * Does the request match a route per the matching rules?
     *
     * @param ServerRequestInterface $request The request to match against.
     *
     * @param Route $route The route to match against.
     *
     * @param string $name The route name.
     *
     * @param string $path The request path.
     *
     * @return mixed False on failure, or a Route on match.
     *
     */
    protected function applyRules($request, $route, $name, $path)
    {
        $score = 0;
        foreach ($this->ruleIterator as $rule) {
            if (! $rule($request, $route)) {
                return $this->ruleFailed($request, $route, $name, $path, $rule, $score);
            }
            $score ++;
        }
        return $this->routeMatched($route, $name, $path);
    }

    /**
     *
     * A matching rule failed.
     *
     * @param ServerRequestInterface $request The request to match against.
     *
     * @param Route $route The route to match against.
     *
     * @param string $name The route name.
     *
     * @param string $path The request path.
     *
     * @param mixed $rule The rule that failed.
     *
     * @param int $score The failure score.
     *
     * @return false
     *
     */
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

    /**
     *
     * The route matched.
     *
     * @param Route $route The route to match against.
     *
     * @param string $name The route name.
     *
     * @param string $path The request path.
     *
     * @return Route
     *
     */
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
     * @return ?Route
     *
     */
    public function getFailedRoute()
    {
        return $this->failedRoute;
    }

    /****
     * Retrieves the result of the most recent route matching attempt.
     *
     * @return Route|false|null The matched Route object, false if no route matched, or null if matching has not been attempted.
     */
    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }

    /****
     * Traverses the route map tree according to the given URL path segments and returns an iterator over the matching subtree of routes.
     *
     * @param string $path The URL path to match, e.g., "/users/123".
     * @return \RecursiveArrayIterator Iterator over the subtree of routes matching the path segments.
     */
    private function getMatchedTree($path)
    {
        $node = $this->map->getAsTreeRouteNode();
        foreach (explode('/', trim($path, '/')) as $segment) {
            if (isset($node[$segment])) {
                $node = $node[$segment];
                continue;
            }
            if (isset($node['{}'])) {
                $node = $node['{}'];
            }
        }

        return new \RecursiveArrayIterator($node);
    }
}
