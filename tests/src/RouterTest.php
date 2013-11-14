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

    protected function newRouter($attach = null)
    {
        return new Router(new RouteFactory);
    }
    
    protected function assertIsRoute($actual)
    {
        $this->assertInstanceOf('Aura\Router\Route', $actual);
    }
    
    public function testBeforeAndAfterAttach()
    {
        $this->router->add('before', '/foo');
        $this->router->attach('during:', '/during', function ($router) {
            $router->setNameParam('action');
            $router->setTokens(array('id' => '\d+'));
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
        
        $before = $routes['before'];
        $this->assertIsRoute($before);
        $this->assertSame(array(), $before->tokens);
        $this->assertSame(array(), $before->values);
        $this->assertSame(null, $before->secure);
        $this->assertSame(null, $before->wildcard);
        $this->assertSame(true, $before->routable);
        $this->assertSame(null, $before->is_match);
        $this->assertSame(null, $before->generate);
        
        $during = $routes['during:bar'];
        $this->assertIsRoute($during);
        $this->assertSame(array('id' => '\d+'), $during->tokens);
        $this->assertSame(array('controller' => 'foo', 'action' => 'bar'), $during->values);
        $this->assertSame(true, $during->secure);
        $this->assertSame('other', $during->wildcard);
        $this->assertSame(false, $during->routable);
        $this->assertInstanceOf('Closure', $during->is_match);
        $this->assertInstanceOf('Closure', $during->generate);
        
        $after = $routes['after'];
        $this->assertIsRoute($after);
        $this->assertSame(array(), $after->tokens);
        $this->assertSame(array(), $after->values);
        $this->assertSame(null, $after->secure);
        $this->assertSame(null, $after->wildcard);
        $this->assertSame(true, $after->routable);
        $this->assertSame(null, $after->is_match);
        $this->assertSame(null, $after->generate);
    }
    
    
    public function testAddAndGenerate()
    {
        $this->router->attach('resource:', '/resource', function ($router) {
            
            $router->setTokens(array(
                'id' => '(\d+)',
            ));
            
            $router->setValues(array(
                'controller' => 'resource',
            ));
            
            $router->setNameParam('action');
            
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
        $this->assertSame('resource:read', $actual->name);
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
        $this->assertSame('resource:edit', $actual->name);
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
        $this->assertSame('resource:add', $actual->name);
        
        // delete
        $server = array('REQUEST_METHOD' => 'DELETE');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertIsRoute($actual);
        $this->assertSame('resource:delete', $actual->name);
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
        $this->assertSame('resource:patch', $actual->name);
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
        $this->assertSame('resource:options', $actual->name);
        $expect_values = array(
            'controller' => 'resource',
            'action' => 'options',
            'id' => '42',
            'REQUEST_METHOD' => 'OPTIONS',
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // get a named route
        $actual = $this->router->generate('resource:read', array(
            'id' => 42,
            'format' => null,
        ));
        $this->assertSame('/resource/42', $actual);
        
        // fail to match
        $this->setExpectedException('Aura\Router\Exception\RouteNotFound');
        $actual = $this->router->generate('no-route');
    }
    
    public function testGenerate()
    {
        $this->router->attach('page:', '/page', function ($router) {
            
            $router->setTokens(array(
                'id'            => '(\d+)',
                'format'        => '(\.[^/]+)?',
            ));
            
            $router->setValues(array(
                'controller' => 'page',
                'format' => null,
            ));
            
            $router->add('browse',  '/');
            $router->add('read',    '/{id}{format}');
            $router->add('edit',    '/{id}/edit');
            $router->add('add',     '/add');
            $router->add('delete',  '/{id}/delete');
            
        });
        
    }
    
    public function testGetAndSetRoutes()
    {
        $this->router->attach('page:', '/page', function ($router) {
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
        $this->assertTrue(is_array($actual));
        $this->assertTrue(count($actual) == 2);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page:browse']);
        $this->assertEquals('/page/', $actual['page:browse']->path);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page:read']);
        $this->assertEquals('/page/{id}{format}', $actual['page:read']->path);
        
        // emulate caching the values
        $saved = serialize($actual);
        $restored = unserialize($saved);
        
        // set routes from the restored values
        $router = $this->newRouter();
        $router->setRoutes($restored);
        $actual = $router->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertTrue(count($actual) == 2);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page:browse']);
        $this->assertEquals('/page/', $actual['page:browse']->path);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page:read']);
        $this->assertEquals('/page/{id}{format}', $actual['page:read']->path);
        
    }
    
    public function testGetLog()
    {
        // this is weak. we should actually see if the log contains anything.
        $this->assertSame(array(), $this->router->getLog());
    }
}
