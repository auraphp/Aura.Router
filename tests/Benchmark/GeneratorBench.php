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

    public function setUp()
    {
        $map = new Map(new Route());
        foreach ($this->routesProvider() as $key => $route) {
            $map->get($key, $route, static function () use ($key) { return $key; });
        }

        $map->get('dummy', '/api/user/{id}/{action}/{controller:[a-zA-Z][a-zA-Z0-9_-]{1,}}{/param1,param2}');
        $this->generator = new Generator($map);
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
     * @Revs(1000)
     * @Iterations (10)
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