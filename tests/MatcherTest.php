<?php
namespace Aura\Router;

use Phly\Http\ServerRequestFactory;

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
        $container->setLoggerFactory(function () {
            return new FakeLogger();
        });

        $this->map = $container->getMap();
        $this->matcher = $container->getMatcher();
        $this->logger = $container->getLogger();
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

    public function testAddAndGenerate()
    {
        $this->map->attach('resource', '/resource', function ($map) {

            $map->setTokens(array(
                'id' => '(\d+)',
            ));

            $map->addGet(null, '/')
                ->addDefaults(array(
                    'action' => 'browse'
                ));

            $map->addHead('head', '/{id}');
            $map->addGet('read', '/{id}');
            $map->addPost('edit', '/{id}');
            $map->addPut('add', '/{id}');
            $map->addDelete('delete', '/{id}');
            $map->addPatch('patch', '/{id}');
            $map->addOptions('options', '/{id}');
        });

        // fail to match
        $request = $this->newRequest('/foo/bar/baz/dib');
        $actual = $this->matcher->match($request);
        $this->assertFalse($actual);
        $this->assertFalse($this->matcher->getMatchedRoute());

        // unnamed browse
        $request = $this->newRequest('/resource/', ['REQUEST_METHOD' => 'GET']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('browse', $actual->attributes['action']);
        $this->assertSame(null, $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());

        // head
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'HEAD']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.head', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.head',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);

        // read
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'GET']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.read', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.read',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);

        // edit
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'POST']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.edit', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.edit',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);

        // add
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'PUT']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.add', $actual->attributes['action']);
        $this->assertSame('resource.add', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.add',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);

        // delete
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'DELETE']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.delete', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.delete',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);

        // patch
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'PATCH']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.patch', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.patch',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);

        // options
        $request = $this->newRequest('/resource/42', ['REQUEST_METHOD' => 'OPTIONS']);
        $actual = $this->matcher->match($request);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.options', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.options',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);
    }

    public function testLogger()
    {
        $foo = $this->map->add('foo', '/foo');
        $bar = $this->map->add('bar', '/bar');
        $baz = $this->map->add('baz', '/baz');

        $request = $this->newRequest('/bar');
        $this->matcher->match($request);

        $expect = [
            'debug: /bar FAILED Aura\Router\Matcher\Path ON foo',
            'debug: /bar MATCHED ON bar',
        ];
        $actual = $this->logger->lines;
        $this->assertSame($expect, $actual);

        $this->assertRoute($bar, $this->matcher->getMatchedRoute());
    }

    public function testCatchAll()
    {
        $this->map->add(null, '{/controller,action,id}');

        $request = $this->newRequest('/');
        $actual = $this->matcher->match($request);
        $expect = array(
            'attributes' => array(
                'controller' => null,
                'action' => null,
                'id' => null,
            ),
        );
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->matcher->getMatchedRoute());

        $request = $this->newRequest('/foo');
        $actual = $this->matcher->match($request);
        $expect = array(
            'attributes' => array(
                'controller' => 'foo',
                'action' => null,
                'id' => null,
            ),
        );
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->matcher->getMatchedRoute());

        $request = $this->newRequest('/foo/bar');
        $actual = $this->matcher->match($request);
        $expect = array(
            'attributes' => array(
                'controller' => 'foo',
                'action' => 'bar',
                'id' => null,
            ),
        );
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->matcher->getMatchedRoute());

        $request = $this->newRequest('/foo/bar/baz');
        $actual = $this->matcher->match($request);
        $expect = array(
            'attributes' => array(
                'controller' => 'foo',
                'action' => 'bar',
                'id' => 'baz',
            ),
        );
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->matcher->getMatchedRoute());
    }

    public function testGetFailedRouteIsBestMatch()
    {
        $expect = $this->map->addPost('bar', '/bar');
        $this->map->add('foo', '/foo');

        $request = $this->newRequest('/bar');
        $match = $this->matcher->match($request);
        $this->assertFalse($match);

        $actual = $this->matcher->getFailedRoute();
        $this->assertSame($expect, $actual);
    }

    public function testGetFailedRouteIsBestMatchWithPriorityGivenToThoseAddedFirst()
    {
        $expect = $this->map->addPost('post_bar', '/bar');
        $other = $this->map->addDelete('delete_bar', '/bar');

        $request = $this->newRequest('/bar');
        $match = $this->matcher->match($request);
        $this->assertFalse($match);

        $this->assertSame($expect, $this->matcher->getFailedRoute());
        $this->assertEquals($expect->score, $other->score, "Assert scores were actually equal");
    }
}
