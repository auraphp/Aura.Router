<?php
namespace Aura\Router;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class MapTest extends TestCase
{
    protected $map;

    protected function set_up()
    {
        parent::set_up();
        $container = new RouterContainer();
        $this->map = $container->getMap();
    }

    protected function assertIsRoute($actual)
    {
        $this->assertInstanceof('Aura\Router\Route', $actual);
    }

    protected function assertRoute($expect, $actual)
    {
        $this->assertIsRoute($actual);
        foreach ($expect as $key => $val) {
            $this->assertSame($val, $actual->$key);
        }
    }

    public function testRouteAlreadyExists()
    {
        $this->map->route('foo', '/foo');
        $this->expectException('Aura\Router\Exception\RouteAlreadyExists');
        $this->map->route('foo', '/foo');
    }

    public function testRouteWithoutName()
    {
        $route = $this->map->route(null, '/foo');
        $this->assertEmpty($route->name);
    }

    public function testBeforeAndAfterAttach()
    {
        $this->map->route('before', '/foo');

        $this->map->attach('during.', '/during', function ($map) {
            $map->tokens(['id' => '\d+']);
            $map->allows('GET');
            $map->defaults(['zim' => 'gir']);
            $map->secure(true);
            $map->wildcard('other');
            $map->isRoutable(false);
            $map->route('bar', '/bar');
        });

        $this->map->route('after', '/baz');

        $map = $this->map->getRoutes();

        $expect = [
            'tokens' => [],
            'allows' => [],
            'defaults' => [],
            'secure' => null,
            'wildcard' => null,
            'isRoutable' => true,
        ];
        $this->assertRoute($expect, $map['before']);
        $this->assertRoute($expect, $map['after']);

        $actual = $map['during.bar'];
        $expect = [
            'tokens' => ['id' => '\d+'],
            'allows' => ['GET'],
            'defaults' => ['zim' => 'gir'],
            'secure' => true,
            'wildcard' => 'other',
            'isRoutable' => false,
        ];
        $this->assertRoute($expect, $actual);
    }

    public function testAttachInAttach()
    {
        $this->map->attach('foo.', '/foo', function ($map) {
            $map->route('index', '/index');
            $map->attach('bar.', '/bar', function ($map) {
                $map->route('index', '/index');
            });
        });

        $map = $this->map->getRoutes();

        $this->assertSame('/foo/index', $map['foo.index']->path);
        $this->assertSame('/foo/bar/index', $map['foo.bar.index']->path);
    }

    public function testGetAndSetRoutes()
    {
        $this->map->attach('page.', '/page', function ($map) {
            $map->tokens([
                'id'            => '\d+',
                'format'        => '(\.[^/]+)?',
            ]);

            $map->defaults([
                'controller' => 'page',
                'format' => null,
            ]);

            $map->route('browse', '/');
            $map->route('read', '/{id}{format}');
        });

        $actual = $this->map->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertCount(2, $actual);
        $this->assertIsRoute($actual['page.browse']);
        $this->assertEquals('/page/', $actual['page.browse']->path);
        $this->assertIsRoute($actual['page.read']);
        $this->assertEquals('/page/{id}{format}', $actual['page.read']->path);

        // emulate caching the values
        $saved = serialize($actual);
        $restored = unserialize($saved);

        // set routes in new map from the restored values
        $container = new RouterContainer();
        $map = $container->getMap();
        $map->setRoutes($restored);
        $actual = $map->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertCount(2, $actual);
        $this->assertIsRoute($actual['page.browse']);
        $this->assertEquals('/page/', $actual['page.browse']->path);
        $this->assertIsRoute($actual['page.read']);
        $this->assertEquals('/page/{id}{format}', $actual['page.read']->path);
    }

    public function testGetAsTreeRouteNodeSuccess()
    {
        $routes = [
            (new Route())->path('/api/users'),
            (new Route())->path('/api/users/{id}'),
            (new Route())->path('/api/users/{id}/delete'),
            (new Route())->path('/api/archive{/year,month,day}'),
            (new Route())->path('/api/{controller:[a-zA-Z][a-zA-Z0-9_-]{1,}}/{action}'),
            (new Route())->path('/api/users/{id}/not-routeable')->isRoutable(false),
        ];
        $sut = new Map(new Route());
        $sut->setRoutes($routes);

        $result = $sut->getAsTreeRouteNode();

        $this->assertSame([
            'api' => [
                'users' => [
                    spl_object_hash($routes[0]) => $routes[0],
                    '{}' => [
                        spl_object_hash($routes[1]) => $routes[1],
                        spl_object_hash($routes[2]) => $routes[2],
                        'delete' => [
                            spl_object_hash($routes[2]) => $routes[2],
                        ],
                    ],
                ],
                'archive' => [
                    spl_object_hash($routes[3]) => $routes[3],
                    '{}' => [ // year
                        spl_object_hash($routes[3]) => $routes[3],
                        '{}' => [ // month
                            spl_object_hash($routes[3]) => $routes[3],
                            '{}' => [ // day
                                spl_object_hash($routes[3]) => $routes[3],
                            ],
                        ],
                    ],
                ],
                '{}' => [
                    spl_object_hash($routes[4]) => $routes[4],
                    '{}' => [
                        spl_object_hash($routes[4]) => $routes[4],
                    ],
                ],
            ],
        ], $result);
    }
}
