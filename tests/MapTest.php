<?php
namespace Aura\Router;

class MapTest extends \PHPUnit_Framework_TestCase
{
    protected $map;

    protected function setUp()
    {
        parent::setUp();
        $this->map = $this->newRoutes();
    }

    protected function newRoutes()
    {
        return new Map(new RouteFactory());
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

    public function testBeforeAndAfterAttach()
    {
        $this->map->add('before', '/foo');

        $this->map->attach('during', '/during', function ($router) {
            $router->setTokens(array('id' => '\d+'));
            $router->setMethod('GET');
            $router->setValues(array('zim' => 'gir'));
            $router->setSecure(true);
            $router->setWildcard('other');
            $router->setRoutable(false);
            $router->add('bar', '/bar');
        });

        $this->map->add('after', '/baz');

        $map = $this->map->getRoutes();

        $expect = array(
            'tokens' => array(),
            'server' => array(),
            'method' => array(),
            'values' => array('action' => 'before'),
            'secure' => null,
            'wildcard' => null,
            'routable' => true,
        );
        $this->assertRoute($expect, $map['before']);

        $expect['values']['action'] = 'after';
        $this->assertRoute($expect, $map['after']);

        $actual = $map['during.bar'];
        $expect = array(
            'tokens' => array('id' => '\d+'),
            'method' => array('GET'),
            'values' => array('zim' => 'gir', 'action' => 'during.bar'),
            'secure' => true,
            'wildcard' => 'other',
            'routable' => false,
        );
        $this->assertRoute($expect, $actual);
    }

    public function testAttachInAttach()
    {
        $this->map->attach('foo', '/foo', function ($router) {
            $router->add('index', '/index');
            $router->attach('bar', '/bar', function ($router) {
                $router->add('index', '/index');
            });
        });

        $map = $this->map->getRoutes();

        $this->assertSame('/foo/index', $map['foo.index']->path);
        $this->assertSame('/foo/bar/index', $map['foo.bar.index']->path);
    }

    public function testGetAndSetRoutes()
    {
        $this->map->attach('page', '/page', function ($router) {
            $router->setTokens(array(
                'id'            => '(\d+)',
                'format'        => '(\.[^/]+)?',
            ));

            $router->setValues(array(
                'controller' => 'page',
                'format' => null,
            ));

            $router->add('browse', '/');
            $router->add('read', '/{id}{format}');
        });

        $actual = $this->map->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertTrue(count($actual) == count($this->map));
        $this->assertInstanceOf('Aura\Router\Route', $actual['page.browse']);
        $this->assertEquals('/page/', $actual['page.browse']->path);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page.read']);
        $this->assertEquals('/page/{id}{format}', $actual['page.read']->path);

        // emulate caching the values
        $saved = serialize($actual);
        $restored = unserialize($saved);

        // set routes from the restored values
        $router = $this->newRoutes();
        $router->setRoutes($restored);
        $actual = $router->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertTrue(count($actual) == count($this->map));
        $this->assertInstanceOf('Aura\Router\Route', $actual['page.browse']);
        $this->assertEquals('/page/', $actual['page.browse']->path);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page.read']);
        $this->assertEquals('/page/{id}{format}', $actual['page.read']->path);
    }

    public function testAttachResource()
    {
        $this->map->attachResource('blog', '/api/v1/blog');
        $map = $this->map->getRoutes();

        $expect = array(
            'name' => 'blog.browse',
            'path' => '/api/v1/blog{format}',
            'tokens' => array(
                'id' => '\d+',
                'format' => '(\.[^/]+)?',
            ),
            'method' => array('GET'),
            'values' => array(
                'action' => 'blog.browse',
            ),
        );
        $this->assertRoute($expect, $map['blog.browse']);

        $expect = array(
            'name' => 'blog.read',
            'path' => '/api/v1/blog/{id}{format}',
            'tokens' => array(
                'id' => '\d+',
                'format' => '(\.[^/]+)?',
            ),
            'method' => array('GET'),
            'values' => array(
                'action' => 'blog.read',
            ),
        );
        $this->assertRoute($expect, $map['blog.read']);

        $expect = array(
            'name' => 'blog.add',
            'path' => '/api/v1/blog/add',
            'tokens' => array(
                'id' => '\d+',
                'format' => '(\.[^/]+)?',
            ),
            'method' => array('GET'),
            'values' => array(
                'action' => 'blog.add',
            ),
        );
        $this->assertRoute($expect, $map['blog.add']);

        $expect = array(
            'name' => 'blog.edit',
            'path' => '/api/v1/blog/{id}/edit{format}',
            'tokens' => array(
                'id' => '\d+',
                'format' => '(\.[^/]+)?',
            ),
            'method' => array('GET'),
            'values' => array(
                'action' => 'blog.edit',
            ),
        );
        $this->assertRoute($expect, $map['blog.edit']);

        $expect = array(
            'name' => 'blog.delete',
            'path' => '/api/v1/blog/{id}',
            'tokens' => array(
                'id' => '\d+',
                'format' => '(\.[^/]+)?',
            ),
            'method' => array('DELETE'),
            'values' => array(
                'action' => 'blog.delete',
            ),
        );
        $this->assertRoute($expect, $map['blog.delete']);

        $expect = array(
            'name' => 'blog.create',
            'path' => '/api/v1/blog',
            'tokens' => array(
                'id' => '\d+',
                'format' => '(\.[^/]+)?',
            ),
            'method' => array('POST'),
            'values' => array(
                'action' => 'blog.create',
            ),
        );
        $this->assertRoute($expect, $map['blog.create']);

        $expect = array(
            'name' => 'blog.update',
            'path' => '/api/v1/blog/{id}',
            'tokens' => array(
                'id' => '\d+',
                'format' => '(\.[^/]+)?',
            ),
            'method' => array('PATCH'),
            'values' => array(
                'action' => 'blog.update',
            ),
        );
        $this->assertRoute($expect, $map['blog.update']);

        $expect = array(
            'name' => 'blog.replace',
            'path' => '/api/v1/blog/{id}',
            'tokens' => array(
                'id' => '\d+',
                'format' => '(\.[^/]+)?',
            ),
            'method' => array('PUT'),
            'values' => array(
                'action' => 'blog.replace',
            ),
        );
        $this->assertRoute($expect, $map['blog.replace']);

    }

    public function testAddWithAction()
    {
        $this->map->add('foo.bar', '/foo/bar', 'DirectAction');
        $actual = $this->map->getRoute('foo.bar');
        $this->assertSame('DirectAction', $actual->values['action']);
    }
}
