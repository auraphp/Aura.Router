<?php
namespace Aura\Router;

class RouteCollectionTest extends \PHPUnit_Framework_TestCase
{
    protected $routes;

    protected function setUp()
    {
        parent::setUp();
        $this->routes = $this->newRoutes();
    }

    protected function newRoutes()
    {
        return new RouteCollection(new RouteFactory());
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
        $this->routes->add('before', '/foo');

        $this->routes->attach('during', '/during', function ($router) {
            $router->setTokens(array('id' => '\d+'));
            $router->setMethod('GET');
            $router->setValues(array('zim' => 'gir'));
            $router->setSecure(true);
            $router->setWildcard('other');
            $router->setRoutable(false);
            $router->setIsMatchCallable(function () { });
            $router->setGenerateCallable(function () { });
            $router->add('bar', '/bar');
        });

        $this->routes->add('after', '/baz');

        $routes = $this->routes->getRoutes();

        $expect = array(
            'tokens' => array(),
            'server' => array(),
            'method' => array(),
            'values' => array('action' => 'before'),
            'secure' => null,
            'wildcard' => null,
            'routable' => true,
            'is_match' => null,
            'generate' => null,
        );
        $this->assertRoute($expect, $routes['before']);

        $expect['values']['action'] = 'after';
        $this->assertRoute($expect, $routes['after']);

        $actual = $routes['during.bar'];
        $expect = array(
            'tokens' => array('id' => '\d+'),
            'method' => array('GET'),
            'values' => array('zim' => 'gir', 'action' => 'during.bar'),
            'secure' => true,
            'wildcard' => 'other',
            'routable' => false,
        );
        $this->assertRoute($expect, $actual);
        $this->assertInstanceOf('Closure', $actual->is_match);
        $this->assertInstanceOf('Closure', $actual->generate);
    }

    public function testAttachInAttach()
    {
        $this->routes->attach('foo', '/foo', function ($router) {
            $router->add('index', '/index');
            $router->attach('bar', '/bar', function ($router) {
                $router->add('index', '/index');
            });
        });

        $routes = $this->routes->getRoutes();

        $this->assertSame('/foo/index', $routes['foo.index']->path);
        $this->assertSame('/foo/bar/index', $routes['foo.bar.index']->path);
    }

    public function testGetAndSetRoutes()
    {
        $this->routes->attach('page', '/page', function ($router) {
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

        $actual = $this->routes->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertTrue(count($actual) == count($this->routes));
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
        $this->assertTrue(count($actual) == count($this->routes));
        $this->assertInstanceOf('Aura\Router\Route', $actual['page.browse']);
        $this->assertEquals('/page/', $actual['page.browse']->path);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page.read']);
        $this->assertEquals('/page/{id}{format}', $actual['page.read']->path);
    }

    public function testAttachResource()
    {
        $this->routes->attachResource('blog', '/api/v1/blog');
        $routes = $this->routes->getRoutes();

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
        $this->assertRoute($expect, $routes['blog.browse']);

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
        $this->assertRoute($expect, $routes['blog.read']);

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
        $this->assertRoute($expect, $routes['blog.add']);

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
        $this->assertRoute($expect, $routes['blog.edit']);

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
        $this->assertRoute($expect, $routes['blog.delete']);

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
        $this->assertRoute($expect, $routes['blog.create']);

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
        $this->assertRoute($expect, $routes['blog.update']);

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
        $this->assertRoute($expect, $routes['blog.replace']);

    }

    public function testArrayAccess()
    {
        $foo = $this->routes->add('foo', '/foo');

        $this->routes->offsetUnset('foo');
        $this->assertFalse($this->routes->offsetExists('foo'));

        $this->routes->offsetSet('foo', $foo);
        $this->assertTrue($this->routes->offsetExists('foo'));

        $this->setExpectedException('Aura\Router\Exception\UnexpectedValue');
        $this->routes->offsetSet('bar', 'not a route');
    }

    public function testAddWithAction()
    {
        $this->routes->add('foo.bar', '/foo/bar', 'DirectAction');
        $actual = $this->routes->offsetGet('foo.bar');
        $this->assertSame('DirectAction', $actual->values['action']);
    }
}
