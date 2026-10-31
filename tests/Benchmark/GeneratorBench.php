<?php

namespace Aura\Router\Benchmark;

use Aura\Router\Generator;
use Aura\Router\Map;
use Aura\Router\Route;

/**
 * @BeforeMethods("setUp")
 */
class GeneratorBench
{
    /**
     * @var Generator
     */
    private $generator;

    /**
     * Prepares the route map and initializes the Generator instance for benchmarking.
     *
     * Populates the route map with dynamically generated routes and a complex 'dummy' route, then creates the Generator used in benchmark tests.
     */
    public function setUp()
    {
        $map = new Map(new Route());
        foreach ($this->routesProvider() as $key => $route) {
            $map->get($key, $route, static function () use ($key) { return $key; });
        }

        $map->get('dummy', '/api/user/{id}/{action}/{controller:[a-zA-Z][a-zA-Z0-9_-]{1,}}{/param1,param2}');
        $this->generator = new Generator($map);
    }


    /**
     * Yields a series of route paths with incremental segments and numeric suffixes.
     *
     * Each yielded key is a string in the format "{segmentIndex}-{routeNumber}", and the value is the corresponding route path composed of cumulative segments and a numeric suffix.
     *
     * @return \Generator<string, string> Generator yielding route keys and their corresponding paths.
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


    /**
     * Benchmarks the URL generation for the 'dummy' route with a fixed set of parameters.
     *
     * Executes 1000 repetitions per iteration over 10 iterations to measure the performance of the route generator.
     */
    public function benchMatch()
    {
        $this->generator->generate('dummy', [
            'id' => 1,
            'action' => 'doSomethingAction',
            'controller' => 'My_User-Controller1',
            'param1' => 'value1',
            'param2' => 'value2',
        ]);
    }
}