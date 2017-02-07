<?php
namespace Aura\Router;

use Zend\Diactoros\ServerRequestFactory;

class MatcherTest extends \PHPUnit_Framework_TestCase
{
    protected $map;
    protected $matcher;
    protected $logger;
    protected $request;

    protected function setUp()
    {
        parent::setUp();
        $container = new RouterContainer();
        $this->map = $container->getMap();
        $this->matcher = $container->getMatcher();
    }

    protected function newRequest($path, array $server = [])
    {
        $server['REQUEST_URI'] = $path;
        $server = array_merge($_SERVER, $server);
        return ServerRequestFactory::fromGlobals($server);
    }

    protected function assertIsRoute($actual)
    {
        $this->assertInstanceOf('Aura\Router\Route', $actual);
    }

    protected function assertRoute($expect, $actual)
    {
        $this->assertIsRoute($actual);
        foreach ($expect as $key => $val) {
            $this->assertSame($val, $actual->$key);
        }
    }

    public function testAttach()
    {
        $this->map->attach('resource.', '/resource', function ($map) {

            $map->tokens(['id' => '(\d+)']);

            $map->get('google', 'http://google.com/q={q}')
                ->isRoutable(false);
            $map->get('browse', '/', ['action' => 'browse']);
            $map->head('head', '/{id}');
            $map->get('read', '/{id}');
            $map->post('edit', '/{id}');
            $map->put('add', '/{id}');
            $map->delete('delete', '/{id}');
            $map->patch('patch', '/{id}');
            $map->options('options', '/{id}');
        });

        // fail to match
        $request = $this->newRequest('/foo/bar/baz/dib');
        $actual = $this->matcher->match($request);
        $this->assertFalse($actual);
        $this->assertFalse($this->matcher->getMatchedRoute());

        // browse
        $request = $this->newRequest('/resource/', ['REQUEST_METHOD' => 'GET']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.browse', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());

        // head
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'HEAD']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.head', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = ['id' => '42'];
        $this->assertEquals($expect, $actual->attributes);

        // read
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'GET']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.read', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = ['id' => '42'];
        $this->assertEquals($expect, $actual->attributes);

        // edit
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'POST']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.edit', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = ['id' => '42'];
        $this->assertEquals($expect, $actual->attributes);

        // add
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'PUT']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.add', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = ['id' => '42'];
        $this->assertEquals($expect, $actual->attributes);

        // delete
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'DELETE']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.delete', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = ['id' => '42'];
        $this->assertEquals($expect, $actual->attributes);

        // patch
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'PATCH']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.patch', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = ['id' => '42'];
        $this->assertEquals($expect, $actual->attributes);

        // options
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'OPTIONS']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.options', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = ['id' => '42'];
        $this->assertEquals($expect, $actual->attributes);
    }

    public function testCatchAll()
    {
        $this->map->route('catchall', '{/controller,action,id}');

        $request = $this->newRequest('/');
        $actual = $this->matcher->match($request);
        $expect = [
            'attributes' => [
                'controller' => null,
                'action' => null,
                'id' => null,
            ],
        ];
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->matcher->getMatchedRoute());

        $request = $this->newRequest('/foo');
        $actual = $this->matcher->match($request);
        $expect = [
            'attributes' => [
                'controller' => 'foo',
                'action' => null,
                'id' => null,
            ],
        ];
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->matcher->getMatchedRoute());

        $request = $this->newRequest('/foo/bar');
        $actual = $this->matcher->match($request);
        $expect = [
            'attributes' => [
                'controller' => 'foo',
                'action' => 'bar',
                'id' => null,
            ],
        ];
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->matcher->getMatchedRoute());

        $request = $this->newRequest('/foo/bar/baz');
        $actual = $this->matcher->match($request);
        $expect = [
            'attributes' => [
                'controller' => 'foo',
                'action' => 'bar',
                'id' => 'baz',
            ],
        ];
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->matcher->getMatchedRoute());
    }

    public function testGetFailedRouteIsBestMatch()
    {
        $expect = $this->map->post('bar', '/bar');
        $this->map->route('foo', '/foo');

        $request = $this->newRequest('/bar');
        $match = $this->matcher->match($request);
        $this->assertFalse($match);

        $actual = $this->matcher->getFailedRoute();
        $this->assertSame($expect->name, $actual->name);
    }

    public function testGetFailedRouteIsBestMatchWithPriorityGivenToThoseAddedFirst()
    {
        $expect = $this->map->post('post_bar', '/bar');
        $other = $this->map->delete('delete_bar', '/bar');

        $request = $this->newRequest('/bar');
        $match = $this->matcher->match($request);
        $this->assertFalse($match);

        $failed = $this->matcher->getFailedRoute();
        $this->assertSame($expect->name, $failed->name);
    }

    public function testLogger()
    {
        $container = new RouterContainer();
        $container->setLoggerFactory(function () {
            return new FakeLogger();
        });

        $map = $container->getMap();
        $matcher = $container->getMatcher();
        $logger = $container->getLogger();

        $foo = $map->route('foo', '/foo');
        $bar = $map->route('bar', '/bar');
        $baz = $map->route('baz', '/baz');

        $request = $this->newRequest('/bar');
        $matcher->match($request);

        $expect = [
            'debug: /bar FAILED Aura\Router\Rule\Path ON foo',
            'debug: /bar MATCHED ON bar',
        ];
        $actual = $logger->lines;
        $this->assertSame($expect, $actual);
        $this->assertRoute($bar, $matcher->getMatchedRoute());
    }

    public function testIsCustomMatchWithClosure()
    {
        $route = $this->map->get('foo_bar', '/foo/bar');
        $route->setIsMatchCallable(function(\Psr\Http\Message\ServerRequestInterface $request, \Aura\Router\Route $route) {
            $route->extras(['zim' => 'gir']);
            return true;
        });

        $request = $this->newRequest('/foo/bar');
        $match = $this->matcher->match($request);
        $this->assertIsRoute($match);
        $this->assertEquals('gir', $match->extras['zim']);

        $route = $this->map->post('foo_bar_baz', '/foo/bar/baz');
        $route->setIsMatchCallable(function(\Psr\Http\Message\ServerRequestInterface $request, \Aura\Router\Route $route) {
            return false;
        });
        $request = $this->newRequest('/foo/bar/baz');
        $match = $this->matcher->match($request);
        $this->assertFalse($match);
    }

    public function testIsCustomMatchWithCallback()
    {
        $route = $this->map->get('foo_bar', '/foo/bar');
        $route->setIsMatchCallable(array($this, 'callbackForIsMatchTrue'));

        $request = $this->newRequest('/foo/bar');
        $match = $this->matcher->match($request);

        $this->assertIsRoute($match);
        $this->assertEquals('gir', $match->extras['zim']);

        // second
        $route = $this->map->get('foo_bar_baz', '/foo/bar/baz');
        $route->setIsMatchCallable(array($this, 'callbackForIsMatchFalse'));

        $request = $this->newRequest('/foo/bar/baz');
        $match = $this->matcher->match($request);

        // even though path is correct, should fail because of the closure
        $this->assertFalse($match);

        // Get failed route
        $failedRoute = $this->matcher->getFailedRoute();
        $this->assertRoute($route, $failedRoute);

        // Get failure reason
        $this->assertSame(\Aura\Router\Route::FAILED_CUSTOM, $failedRoute->failedRule);
    }

    public function callbackForIsMatchTrue(\Psr\Http\Message\ServerRequestInterface $request, \Aura\Router\Route $route)
    {
        $route->extras(['zim' => 'gir']);
        return true;
    }

    public function callbackForIsMatchFalse(\Psr\Http\Message\ServerRequestInterface $request, \Aura\Router\Route $route)
    {
        return false;
    }
}
