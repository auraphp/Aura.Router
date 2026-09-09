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
     * @Iterations(3)
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

    /**
     * @param string $url
     * @return ServerRequestInterface
     */
    private function stringToRequest($url)
    {
        return new ServerRequest('GET', $url, [], null, '1.1', []);
    }
}