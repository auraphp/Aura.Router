<?php
namespace Aura\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    protected $router;

    protected function setUp()
    {
        parent::setUp();
        $this->router = $this->newRouter();
    }

    protected function newRouter($basepath = null)
    {
        $factory = new RouterFactory($basepath);
        return $factory->newInstance();
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
        $this->router->add('before', '/foo');

        $this->router->attach('during', '/during', function ($router) {
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

        $this->router->add('after', '/baz');

        $routes = $this->router->getRoutes();

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
        $this->router->attach('foo', '/foo', function ($router) {
            $router->add('index', '/index');
            $router->attach('bar', '/bar', function ($router) {
                $router->add('index', '/index');
            });
        });

        $routes = $this->router->getRoutes();

        $this->assertSame('/foo/index', $routes['foo.index']->path);
        $this->assertSame('/foo/bar/index', $routes['foo.bar.index']->path);
    }

    public function testAddAndGenerate()
    {
        $this->router->attach('resource', '/resource', function ($router) {

            $router->setTokens(array(
                'id' => '(\d+)',
            ));

            $router->addGet(null, '/')
                ->addValues(array(
                    'action' => 'browse'
                ));

            $router->addHead('head', '/{id}');
            $router->addGet('read', '/{id}');
            $router->addPost('edit', '/{id}');
            $router->addPut('add', '/{id}');
            $router->addDelete('delete', '/{id}');
            $router->addPatch('patch', '/{id}');
            $router->addOptions('options', '/{id}');
        });

        // fail to match
        $actual = $this->router->match('/foo/bar/baz/dib');
        $this->assertFalse($actual);
        $this->assertFalse($this->router->getMatchedRoute());

        // unnamed browse
        $server = array('REQUEST_METHOD' => 'GET');
        $actual = $this->router->match('/resource/', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('browse', $actual->params['action']);
        $this->assertSame(null, $actual->name);
        $this->assertRoute($actual, $this->router->getMatchedRoute());

        // head
        $server = array('REQUEST_METHOD' => 'HEAD');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.head', $actual->name);
        $this->assertRoute($actual, $this->router->getMatchedRoute());
        $expect_values = array(
            'action' => 'resource.head',
            'id' => '42',
        );
        // read
        $server = array('REQUEST_METHOD' => 'GET');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.read', $actual->name);
        $this->assertRoute($actual, $this->router->getMatchedRoute());
        $expect_values = array(
            'action' => 'resource.read',
            'id' => '42',
        );
        $this->assertEquals($expect_values, $actual->params);

        // edit
        $server = array('REQUEST_METHOD' => 'POST');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.edit', $actual->name);
        $this->assertRoute($actual, $this->router->getMatchedRoute());
        $expect_values = array(
            'action' => 'resource.edit',
            'id' => '42',
        );
        $this->assertEquals($expect_values, $actual->params);

        // add
        $server = array('REQUEST_METHOD' => 'PUT');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.add', $actual->params['action']);
        $this->assertSame('resource.add', $actual->name);
        $this->assertRoute($actual, $this->router->getMatchedRoute());

        // delete
        $server = array('REQUEST_METHOD' => 'DELETE');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.delete', $actual->name);
        $this->assertRoute($actual, $this->router->getMatchedRoute());
        $expect_values = array(
            'action' => 'resource.delete',
            'id' => '42',
        );
        $this->assertEquals($expect_values, $actual->params);

        // patch
        $server = array('REQUEST_METHOD' => 'PATCH');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.patch', $actual->name);
        $this->assertRoute($actual, $this->router->getMatchedRoute());
        $expect_values = array(
            'action' => 'resource.patch',
            'id' => '42',
        );
        $this->assertEquals($expect_values, $actual->params);

        // options
        $server = array('REQUEST_METHOD' => 'OPTIONS');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.options', $actual->name);
        $this->assertRoute($actual, $this->router->getMatchedRoute());
        $expect_values = array(
            'action' => 'resource.options',
            'id' => '42',
        );
        $this->assertEquals($expect_values, $actual->params);

        // get a named route
        $actual = $this->router->generate('resource.read', array(
            'id' => 42,
            'format' => null,
        ));
        $this->assertSame('/resource/42', $actual);

        // fail to match
        $this->setExpectedException('Aura\Router\Exception\RouteNotFound');
        $actual = $this->router->generate('no-route');
    }

    public function testGetAndSetRoutes()
    {
        $this->router->attach('page', '/page', function ($router) {
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

        $actual = $this->router->getRoutes();
        $this->assertInstanceOf('Aura\Router\RouteCollection', $actual);
        $this->assertTrue(count($actual) == 2);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page.browse']);
        $this->assertEquals('/page/', $actual['page.browse']->path);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page.read']);
        $this->assertEquals('/page/{id}{format}', $actual['page.read']->path);

        // emulate caching the values
        $saved = serialize($actual);
        $restored = unserialize($saved);

        // set routes from the restored values
        $router = $this->newRouter();
        $router->setRoutes($restored);
        $actual = $router->getRoutes();
        $this->assertInstanceOf('Aura\Router\RouteCollection', $actual);
        $this->assertTrue(count($actual) == 2);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page.browse']);
        $this->assertEquals('/page/', $actual['page.browse']->path);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page.read']);
        $this->assertEquals('/page/{id}{format}', $actual['page.read']->path);
    }

    public function testGetDebug()
    {
        $foo = $this->router->add(null, '/foo');
        $bar = $this->router->add(null, '/bar');
        $baz = $this->router->add(null, '/baz');

        $this->router->match('/bar');

        $actual = $this->router->getDebug();
        $expect = array($foo, $bar);
        $this->assertSame($expect, $actual);
        $this->assertRoute($bar, $this->router->getMatchedRoute());
    }

    public function testAttachResource()
    {
        $this->router->attachResource('blog', '/api/v1/blog');
        $routes = $this->router->getRoutes();

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

    public function testCatchAll()
    {
        $this->router->add(null, '{/controller,action,id}');

        $actual = $this->router->match('/', array());
        $expect = array(
            'params' => array(
                'controller' => null,
                'action' => null,
                'id' => null,
            ),
        );
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->router->getMatchedRoute());

        $actual = $this->router->match('/foo', array());
        $expect = array(
            'params' => array(
                'controller' => 'foo',
                'action' => null,
                'id' => null,
            ),
        );
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->router->getMatchedRoute());

        $actual = $this->router->match('/foo/bar', array());
        $expect = array(
            'params' => array(
                'controller' => 'foo',
                'action' => 'bar',
                'id' => null,
            ),
        );
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->router->getMatchedRoute());

        $actual = $this->router->match('/foo/bar/baz', array());
        $expect = array(
            'params' => array(
                'controller' => 'foo',
                'action' => 'bar',
                'id' => 'baz',
            ),
        );
        $this->assertRoute($expect, $actual);
        $this->assertRoute($expect, $this->router->getMatchedRoute());
    }

    public function testArrayAccess()
    {
        $foo = $this->router->add('foo', '/foo');

        $this->router->offsetUnset('foo');
        $this->assertFalse($this->router->offsetExists('foo'));

        $this->router->offsetSet('foo', $foo);
        $this->assertTrue($this->router->offsetExists('foo'));

        $this->setExpectedException('Aura\Router\Exception\UnexpectedValue');
        $this->router->offsetSet('bar', 'not a route');
    }

    public function testGetFailedRouteIsBestMatch()
    {
        $post_bar = $this->router->addPost('bar', '/bar');
        $this->router->add('foo', '/foo');
        $route = $this->router->match('/bar', array());
        $this->assertFalse($route);
        $failed_route = $this->router->getFailedRoute();
        $this->assertSame($post_bar, $failed_route);
    }

    public function testGetFailedRouteIsBestMatchWithPriorityGivenToThoseAddedFirst()
    {
        $post_bar = $this->router->addPost('post_bar', '/bar');
        $delete_bar = $this->router->addDelete('delete_bar', '/bar');

        $route = $this->router->match('/bar', array());

        $this->assertFalse($route);
        $this->assertSame($post_bar, $this->router->getFailedRoute());
        $this->assertEquals($post_bar->score, $delete_bar->score, "Assert scores were actually equal");
    }

    public function testGenerateRaw()
    {
        $this->router->add('asset', '/{vendor}/{package}/{file}');
        $data = array(
            'vendor' => 'vendor+name',
            'package' => 'package+name',
            'file' => 'foo/bar/baz.jpg',
        );
        $actual = $this->router->generateRaw('asset', $data);
        $expect = '/vendor+name/package+name/foo/bar/baz.jpg';
        $this->assertSame($actual, $expect);
    }

    public function testAddWithAction()
    {
        $this->router->add('foo.bar', '/foo/bar', 'DirectAction');
        $actual = $this->router->match('/foo/bar');
        $this->assertSame('DirectAction', $actual->values['action']);
    }

    public function testWithBasepathIndex()
    {
        $this->router = $this->newRouter('/path/to/sub/index.php');
        $this->router->add('foo.bar', '/foo/bar', 'DirectAction');

        // should fail without basepath
        $this->assertFalse($this->router->match('/foo/bar'));

        // should pass with basepath
        $actual = $this->router->match('/path/to/sub/index.php/foo/bar');
        $this->assertSame('DirectAction', $actual->values['action']);

        // should get the basepath in place
        $expect = '/path/to/sub/index.php/foo/bar';
        $actual = $this->router->generate('foo.bar');
        $this->assertSame($expect, $actual);
    }


    public function testWithBasepathDir()
    {
        $this->router = $this->newRouter('/path/to/sub');
        $this->router->add('foo.bar', '/foo/bar', 'DirectAction');

        // should fail without basepath
        $this->assertFalse($this->router->match('/foo/bar'));

        // should pass with basepath
        $actual = $this->router->match('/path/to/sub/foo/bar');
        $this->assertSame('DirectAction', $actual->values['action']);

        // should get the basepath in place
        $expect = '/path/to/sub/foo/bar';
        $actual = $this->router->generate('foo.bar');
        $this->assertSame($expect, $actual);
    }
}
