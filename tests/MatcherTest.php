<?php
namespace Aura\Router;

class MatcherTest extends \PHPUnit_Framework_TestCase
{
    protected $map;
    protected $matcher;

    protected function setUp()
    {
        parent::setUp();
        $container = new RouterContainer();
        $this->map = $container->getMap();
        $this->matcher = $container->getMatcher();
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
        $actual = $this->matcher->match('/foo/bar/baz/dib');
        $this->assertFalse($actual);
        $this->assertFalse($this->matcher->getMatchedRoute());

        // unnamed browse
        $server = array('REQUEST_METHOD' => 'GET');
        $actual = $this->matcher->match('/resource/', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('browse', $actual->attributes['action']);
        $this->assertSame(null, $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());

        // head
        $server = array('REQUEST_METHOD' => 'HEAD');
        $actual = $this->matcher->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.head', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.head',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);

        // read
        $server = array('REQUEST_METHOD' => 'GET');
        $actual = $this->matcher->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.read', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.read',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);

        // edit
        $server = array('REQUEST_METHOD' => 'POST');
        $actual = $this->matcher->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.edit', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.edit',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);

        // add
        $server = array('REQUEST_METHOD' => 'PUT');
        $actual = $this->matcher->match('/resource/42', $server);
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
        $server = array('REQUEST_METHOD' => 'DELETE');
        $actual = $this->matcher->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.delete', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.delete',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);

        // patch
        $server = array('REQUEST_METHOD' => 'PATCH');
        $actual = $this->matcher->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.patch', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.patch',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);

        // options
        $server = array('REQUEST_METHOD' => 'OPTIONS');
        $actual = $this->matcher->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.options', $actual->name);
        $this->assertRoute($actual, $this->matcher->getMatchedRoute());
        $expect = array(
            'action' => 'resource.options',
            'id' => '42',
        );
        $this->assertEquals($expect, $actual->attributes);
    }

    public function testGetDebug()
    {
        $foo = $this->map->add(null, '/foo');
        $bar = $this->map->add(null, '/bar');
        $baz = $this->map->add(null, '/baz');

        $this->matcher->match('/bar');

        $actual = $this->matcher->getDebug();
        $expect = array($foo, $bar);
        $this->assertSame($expect, $actual);
        $this->assertRoute($bar, $this->matcher->getMatchedRoute());
    }

    public function testCatchAll()
    {
        $this->map->add(null, '{/controller,action,id}');

        $actual = $this->matcher->match('/', array());
        $expect = array(
            'attributes' => array(
                'controller' => null,
                'action' => null,
                'id' => null,
            ),
        );
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->matcher->getMatchedRoute());

        $actual = $this->matcher->match('/foo', array());
        $expect = array(
            'attributes' => array(
                'controller' => 'foo',
                'action' => null,
                'id' => null,
            ),
        );
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->matcher->getMatchedRoute());

        $actual = $this->matcher->match('/foo/bar', array());
        $expect = array(
            'attributes' => array(
                'controller' => 'foo',
                'action' => 'bar',
                'id' => null,
            ),
        );
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->matcher->getMatchedRoute());

        $actual = $this->matcher->match('/foo/bar/baz', array());
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
        $match = $this->matcher->match('/bar', array());
        $this->assertFalse($match);
        $actual = $this->matcher->getFailedRoute();
        $this->assertSame($expect, $actual);
    }

    public function testGetFailedRouteIsBestMatchWithPriorityGivenToThoseAddedFirst()
    {
        $expect = $this->map->addPost('post_bar', '/bar');
        $other = $this->map->addDelete('delete_bar', '/bar');

        $match = $this->matcher->match('/bar', array());

        $this->assertFalse($match);
        $this->assertSame($expect, $this->matcher->getFailedRoute());
        $this->assertEquals($expect->score, $other->score, "Assert scores were actually equal");
    }
}
