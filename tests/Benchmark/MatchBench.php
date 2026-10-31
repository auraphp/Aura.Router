<?php

namespace Aura\Router\Benchmark;

use Aura\Router\RouterContainer;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @BeforeMethods("setUp")
 */
class MatchBench
{
    /** @var RouterContainer $container */
    private $container;
    /**
     * @var \Aura\Router\Route[]|\mixed[][]
     */
    private $treeNodes;

    /**
     * Prepares the router container and populates it with a set of generated GET routes for benchmarking.
     *
     * Initializes the route map with routes from the provider and stores the resulting route tree structure for later use.
     */
    public function setUp()
    {
        $this->container = new RouterContainer();
        $map = $this->container->getMap();

        foreach ($this->routesProvider() as $key => $route) {
            $map->get($key, $route, static function () use ($key) { return $key; });
        }

        $this->treeNodes = $map->getAsTreeRouteNode();
    }

    /**
     * Benchmarks the performance of matching a set of generated routes against the router.
     *
     * Restores the pre-built route tree, then iterates through all generated routes, converting each to a request and verifying that it matches. Throws a RuntimeException if any route fails to match.
     */
    public function benchMatch()
    {
        $this->container->getMap()->treeRoutes = $this->treeNodes;
        $matcher = $this->container->getMatcher();
        foreach ($this->routesProvider() as $route) {
            $result = $matcher->match($this->stringToRequest($route));
            if ($result === false) {
                throw new \RuntimeException(sprintf('Expected route "%s" to be an match', $route));
            }
        }
    }

    /**
     * Generates a sequence of route strings by incrementally building path segments and appending numeric suffixes.
     *
     * Yields 600 routes in total, with each route keyed by its segment index and number (e.g., "2-45") and valued as the corresponding path (e.g., "/one/two/three45").
     *
     * @return \Generator<string, string> Route keys mapped to their corresponding path strings.
     */
    private function routesProvider()
    {
        $segments = ['one', 'two', 'three', 'four', 'five', 'six'];
        $routesPerSegment = 100;

        $routeSegment = '';
        foreach ($segments as $index => $segment) {
            $routeSegment .= '/' . $segment;
            for ($i = 1; $i <= $routesPerSegment; $i++) {
                yield $index . '-' . $i => $routeSegment . $i;
            }
        }
    }

    /****
     * Creates a PSR-7 ServerRequest object for a GET request to the specified URL.
     *
     * @param string $url The URL to use for the request.
     * @return ServerRequestInterface A ServerRequest instance representing a GET request to the given URL.
     */
    private function stringToRequest($url)
    {
        return new ServerRequest('GET', $url, [], null, '1.1', []);
    }
}