<?php
namespace Aura\Router\Helper;

use Aura\Router\Exception\RouteNotFound;
use Aura\Router\RouterContainer;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    protected $container;
    protected $map;
    protected $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $container = new RouterContainer();
        $this->container = $container;
        $this->map = $container->getMap();
        $this->generator = $container->getGenerator();
    }

    public function testInvokeReturnsGeneratedRoute()
    {
        $this->map->route('test', '/blog/{id}/edit')
                  ->tokens([
                      'id' => '([0-9]+)',
                  ]);

        $helper = $this->container->newRouteHelper();
        $this->assertEquals('/blog/4%202/edit', $helper('test', ['id' => '4 2', 'foo' => 'bar']));
    }
}
