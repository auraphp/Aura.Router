<?php
namespace Aura\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    protected $router;

    protected function setUp()
    {
        parent::setUp();
        $this->factory = new RouterFactory;
        $this->router = $this->newRouter();
    }

    protected function newRouter()
    {
        return $this->factory->newInstance();
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
            $router->setServer(array('HTTP_REQUEST' => 'GET'));
            $router->setValues(array('controller' => 'foo'));
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
            'values' => array('controller' => null, 'action' => 'before'),
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
            'server' => array('HTTP_REQUEST' => 'GET'),
            'values' => array('controller' => 'foo', 'action' => 'bar'),
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
            
            $router->setValues(array(
                'controller' => 'resource',
            ));
            
            $router->addGet(null, '/')
                ->addValues(array(
                    'action' => 'browse'
                ));
                
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
        
        // unnamed browse
        $server = array('REQUEST_METHOD' => 'GET');
        $actual = $this->router->match('/resource/', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource', $actual->params['controller']);
        $this->assertSame('browse', $actual->params['action']);
        $this->assertSame(null, $actual->name);
        
        // read
        $server = array('REQUEST_METHOD' => 'GET');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.read', $actual->name);
        $expect_values = array(
            'controller' => 'resource',
            'action' => 'read',
            'id' => '42',
            'REQUEST_METHOD' => 'GET',
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // edit
        $server = array('REQUEST_METHOD' => 'POST');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.edit', $actual->name);
        $expect_values = array(
            'controller' => 'resource',
            'action' => 'edit',
            'id' => '42',
            'REQUEST_METHOD' => 'POST',
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // add
        $server = array('REQUEST_METHOD' => 'PUT');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource', $actual->params['controller']);
        $this->assertSame('add', $actual->params['action']);
        $this->assertSame('resource.add', $actual->name);
        
        // delete
        $server = array('REQUEST_METHOD' => 'DELETE');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.delete', $actual->name);
        $expect_values = array(
            'controller' => 'resource',
            'action' => 'delete',
            'id' => '42',
            'REQUEST_METHOD' => 'DELETE',
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // patch
        $server = array('REQUEST_METHOD' => 'PATCH');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.patch', $actual->name);
        $expect_values = array(
            'controller' => 'resource',
            'action' => 'patch',
            'id' => '42',
            'REQUEST_METHOD' => 'PATCH',
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // options
        $server = array('REQUEST_METHOD' => 'OPTIONS');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource.options', $actual->name);
        $expect_values = array(
            'controller' => 'resource',
            'action' => 'options',
            'id' => '42',
            'REQUEST_METHOD' => 'OPTIONS',
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
            'server' => array(
                'REQUEST_METHOD' => 'GET',
            ),
            'values' => array(
                'controller' => 'blog',
                'action' => 'browse',
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
            'server' => array(
                'REQUEST_METHOD' => 'GET',
            ),
            'values' => array(
                'controller' => 'blog',
                'action' => 'read',
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
            'server' => array(
                'REQUEST_METHOD' => 'GET',
            ),
            'values' => array(
                'controller' => 'blog',
                'action' => 'add',
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
            'server' => array(
                'REQUEST_METHOD' => 'GET',
            ),
            'values' => array(
                'controller' => 'blog',
                'action' => 'edit',
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
            'server' => array(
                'REQUEST_METHOD' => 'DELETE',
            ),
            'values' => array(
                'controller' => 'blog',
                'action' => 'delete',
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
            'server' => array(
                'REQUEST_METHOD' => 'POST',
            ),
            'values' => array(
                'controller' => 'blog',
                'action' => 'create',
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
            'server' => array(
                'REQUEST_METHOD' => 'PATCH',
            ),
            'values' => array(
                'controller' => 'blog',
                'action' => 'update',
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
            'server' => array(
                'REQUEST_METHOD' => 'PUT',
            ),
            'values' => array(
                'controller' => 'blog',
                'action' => 'replace',
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
        
        $actual = $this->router->match('/foo', array());
        $expect = array(
            'params' => array(
                'controller' => 'foo',
                'action' => null,
                'id' => null,
            ),
        );
        $this->assertRoute($expect, $actual);
        
        $actual = $this->router->match('/foo/bar', array());
        $expect = array(
            'params' => array(
                'controller' => 'foo',
                'action' => 'bar',
                'id' => null,
            ),
        );
        $this->assertRoute($expect, $actual);
        
        $actual = $this->router->match('/foo/bar/baz', array());
        $expect = array(
            'params' => array(
                'controller' => 'foo',
                'action' => 'bar',
                'id' => 'baz',
            ),
        );
        $this->assertRoute($expect, $actual);
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
}
