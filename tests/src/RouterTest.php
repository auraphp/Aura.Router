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
        $this->markTestIncomplete();
        // add a route before attach
        // attach a route with set*()
        // add a route after attach
        // check that before and after do not have the set*() values
        // check that attach does have the set*() values
    }
    
    public function testAddAndGenerate()
    {
        $this->router->attach('resource:', '/resource', function ($router) {
            
            $router->setRequire(array(
                'id' => '(\d+)',
            ));
            
            $router->setDefault(array(
                'controller' => 'resource',
            ));
            
            $router->setNameParam('action');
            
            $router->addGet(null, '/', array(
                'default' => array(
                    'action' => 'browse',
                ),
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
            
            $router->setRequire(array(
                'id'            => '(\d+)',
                'format'        => '(\.[^/]+)?',
            ));
            
            $router->setDefault(array(
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
            $router->setRequire(array(
                'id'            => '(\d+)',
                'format'        => '(\.[^/]+)?',
            ));
            
            $router->setDefault(array(
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
