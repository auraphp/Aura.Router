<?php
namespace Aura\Router;

class MapTest extends \PHPUnit_Framework_TestCase
{
    protected $map;

    protected function setUp()
    {
        parent::setUp();
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

    public function testBeforeAndAfterAttach()
    {
        $this->map->add('before', '/foo');

        $this->map->attach('during', '/during', function ($map) {
            $map->setTokens(array('id' => '\d+'));
            $map->setMethod('GET');
            $map->setDefaults(array('zim' => 'gir'));
            $map->setSecure(true);
            $map->setWildcard('other');
            $map->setRoutable(false);
            $map->add('bar', '/bar');
        });

        $this->map->add('after', '/baz');

        $map = $this->map->getRoutes();

        $expect = array(
            'tokens' => array(),
            'server' => array(),
            'method' => array(),
            'defaults' => array('action' => 'before'),
            'secure' => null,
            'wildcard' => null,
            'routable' => true,
        );
        $this->assertRoute($expect, $map['before']);

        $expect['defaults']['action'] = 'after';
        $this->assertRoute($expect, $map['after']);

        $actual = $map['during.bar'];
        $expect = array(
            'tokens' => array('id' => '\d+'),
            'method' => array('GET'),
            'defaults' => array('zim' => 'gir', 'action' => 'during.bar'),
            'secure' => true,
            'wildcard' => 'other',
            'routable' => false,
        );
        $this->assertRoute($expect, $actual);
    }

    public function testAttachInAttach()
    {
        $this->map->attach('foo', '/foo', function ($map) {
            $map->add('index', '/index');
            $map->attach('bar', '/bar', function ($map) {
                $map->add('index', '/index');
            });
        });

        $map = $this->map->getRoutes();

        $this->assertSame('/foo/index', $map['foo.index']->path);
        $this->assertSame('/foo/bar/index', $map['foo.bar.index']->path);
    }

    public function testGetAndSetRoutes()
    {
        $this->map->attach('page', '/page', function ($map) {
            $map->setTokens(array(
                'id'            => '(\d+)',
                'format'        => '(\.[^/]+)?',
            ));

            $map->setDefaults(array(
                'controller' => 'page',
                'format' => null,
            ));

            $map->add('browse', '/');
            $map->add('read', '/{id}{format}');
        });

        $actual = $this->map->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertTrue(count($actual) == count($this->map));
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
        $this->assertTrue(count($actual) == count($this->map));
        $this->assertIsRoute($actual['page.browse']);
        $this->assertEquals('/page/', $actual['page.browse']->path);
        $this->assertIsRoute($actual['page.read']);
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
            'defaults' => array(
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
            'defaults' => array(
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
            'defaults' => array(
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
            'defaults' => array(
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
            'defaults' => array(
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
            'defaults' => array(
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
            'defaults' => array(
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
            'defaults' => array(
                'action' => 'blog.replace',
            ),
        );
        $this->assertRoute($expect, $map['blog.replace']);

    }

    public function testAddWithAction()
    {
        $this->map->add('foo.bar', '/foo/bar', 'DirectAction');
        $actual = $this->map->getRoute('foo.bar');
        $this->assertSame('DirectAction', $actual->defaults['action']);
    }
}
