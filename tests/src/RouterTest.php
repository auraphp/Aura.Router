<?php
namespace Aura\Router;

/**
 * Test class for Router.
 */
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
        return new Router(
            new RouteFactory,
            $attach
        );
    }
    
    public function testAddUnnamedRoute()
    {
        $this->router->add(null, '/foo/bar/baz');
        $actual = $this->router->match('/foo/bar/baz');
        $this->assertInstanceOf('Aura\Router\Route', $actual);
        $this->assertSame('/foo/bar/baz', $actual->path);
    }
    
    public function testAddNamedRoute()
    {
        $this->router->add('zim', '/zim/dib/gir');
        $actual = $this->router->match('/zim/dib/gir');
        $this->assertInstanceOf('Aura\Router\Route', $actual);
        $this->assertSame('/zim/dib/gir', $actual->path);
        $this->assertSame('zim', $actual->name);
    }
    
    public function testAddComplexRoute()
    {
        $this->router->add('read', '/resource/{id}', array(
            'require' => array(
                'id' => '(\d+)',
            ),
            'default' => array(
                'controller' => 'foo',
                'action' => 'bar',
                'zim' => 'gir'
            ),
        ));
        
        $actual = $this->router->match('/resource/42');
        $this->assertInstanceOf('Aura\Router\Route', $actual);
        $this->assertSame('foo', $actual->params['controller']);
        $this->assertSame('bar', $actual->params['action']);
        $this->assertSame('42', $actual->params['id']);
        $this->assertSame('gir', $actual->params['zim']);
    }
    
    public function testAttachWithBadRouteSpec()
    {
        $this->router->attach(null, array(
            'routes' => array(
                'name' => 42,
            ),
        ));
        
        $this->setExpectedException('Aura\Router\Exception\UnexpectedType');
        $this->router->match('/');
    }
    
    public function testAttachRoutesWithoutPathPrefix()
    {
        $type = 'Aura\Router\Route';
        
        $this->router->attach(null, array(
            'routes' => array(
                '/{controller}/{action}/{id}{format}',
                '/{controller}/{action}/{id}',
                '/{controller}/{action}',
                '/{controller}',
                '/',
            ),
            'require' => array(
                'controller'    => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'action'        => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'default'     => array(
                'controller' => 'default_controller',
                'action'     => 'default_action',
                'format' => null,
            ),
        ));
        
        // fail to match
        $actual = $this->router->match('/foo/bar/baz/dib');
        $this->assertFalse($actual);
        
        // path: /
        $actual = $this->router->match('/');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('default_controller', $actual->params['controller']);
        $this->assertSame('default_action', $actual->params['action']);
        
        // path: /controller
        $actual = $this->router->match('/foo');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('foo', $actual->params['controller']);
        $this->assertSame('default_action', $actual->params['action']);
        
        // path: /controller/action
        $actual = $this->router->match('/foo/bar');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('foo', $actual->params['controller']);
        $this->assertSame('bar', $actual->params['action']);
        
        // path: /controller/action/id
        $actual = $this->router->match('/foo/bar/42');
        $expect_values = array(
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => null,
        );
        $this->assertInstanceOf($type, $actual);
        $this->assertEquals($expect_values, $actual->params);
        
        // path: /controller/action/id.format
        $actual = $this->router->match('/foo/bar/42.json');
        $expect_values = array(
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => '.json',
        );
        $this->assertInstanceOf($type, $actual);
        $this->assertEquals($expect_values, $actual->params);
    }
    
    public function testAttachNamedRoutes()
    {
        $type = 'Aura\Router\Route';
        
        $this->router->attach(null, array(
            'routes' => array(
                'browse' => '/',
                'read' => '/{id}{format}',
                'edit' => '/{id}/edit',
                'add' => '/add',
                'delete' => '/{id}/delete',
            ),
            'require' => array(
                'action'        => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'default'     => array(
                'controller' => 'page',
                'format' => null,
            ),
        ));
        
        // fail to match
        $actual = $this->router->match('/foo/bar/baz/dib');
        $this->assertFalse($actual);
        
        // browse
        $actual = $this->router->match('/');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page', $actual->params['controller']);
        $this->assertSame('browse', $actual->params['action']);
        $this->assertSame('browse', $actual->name);
        
        // read
        $actual = $this->router->match('/42');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // read w/ format
        $actual = $this->router->match('/42.json');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => '.json',
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // edit
        $actual = $this->router->match('/42/edit');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('edit', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'edit',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // add
        $actual = $this->router->match('/add');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page', $actual->params['controller']);
        $this->assertSame('add', $actual->params['action']);
        $this->assertSame('add', $actual->name);
        
        // delete
        $actual = $this->router->match('/42/delete');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('delete', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'delete',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
    }
    
    public function testAttachUnnamedLongFormRoutes()
    {
        $type = 'Aura\Router\Route';
        
        $this->router->attach(null, array(
            'routes' => array(
                array('path' => '/{controller}/{action}/{id}{format}'),
                array('path' => '/{controller}/{action}/{id}'),
                array('path' => '/{controller}/{action}'),
                array('path' => '/{controller}'),
                array('path' => '/'),
            ),
            'require' => array(
                'controller'    => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'action'        => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'default'     => array(
                'controller' => 'default_controller',
                'action'     => 'default_action',
                'format' => null,
            ),
        ));
        
        // fail to match
        $actual = $this->router->match('/foo/bar/baz/dib');
        $this->assertFalse($actual);
        
        // path: /
        $actual = $this->router->match('/');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('default_controller', $actual->params['controller']);
        $this->assertSame('default_action', $actual->params['action']);
        
        // path: /controller
        $actual = $this->router->match('/foo');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('foo', $actual->params['controller']);
        $this->assertSame('default_action', $actual->params['action']);
        
        // path: /controller/action
        $actual = $this->router->match('/foo/bar');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('foo', $actual->params['controller']);
        $this->assertSame('bar', $actual->params['action']);
        
        // path: /controller/action/id
        $actual = $this->router->match('/foo/bar/42');
        $this->assertInstanceOf($type, $actual);
        $expect_values = array(
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // path: /controller/action/id.format
        $actual = $this->router->match('/foo/bar/42.json');
        $this->assertInstanceOf($type, $actual);
        $expect_values = array(
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => '.json',
        );
        $this->assertEquals($expect_values, $actual->params);
    }
    
    public function testAttachNamedRoutesWithPrefixes()
    {
        $type = 'Aura\Router\Route';
        
        $this->router->attach('/page', array(
            'routes' => array(
                'browse' => '/',
                'read' => '/{id}{format}',
                'edit' => '/{id}/edit',
                'add' => '/add',
                'delete' => '/{id}/delete',
            ),
            'require' => array(
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'default'     => array(
                'controller' => 'page',
                'format' => null,
            ),
            'name_prefix' => 'page:',
        ));
        
        // fail to match
        $actual = $this->router->match('/foo/bar/baz/dib');
        $this->assertFalse($actual);
        
        // browse
        $actual = $this->router->match('/page/');
        
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page', $actual->params['controller']);
        $this->assertSame('browse', $actual->params['action']);
        $this->assertSame('page:browse', $actual->name);
        
        // read
        $actual = $this->router->match('/page/42');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // read w/ format
        $actual = $this->router->match('/page/42.json');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => '.json',
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // edit
        $actual = $this->router->match('/page/42/edit');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:edit', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'edit',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // add
        $actual = $this->router->match('/page/add');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:add', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'add',
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // delete
        $actual = $this->router->match('/page/42/delete');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:delete', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'delete',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
    }
    
    public function testAttachNamedRestRoutesWithPrefixes()
    {
        $type = 'Aura\Router\Route';
        
        $this->router->attach('/resource', array(
            'routes' => array(
                'browse' => array(
                    'path' => '/',
                    'method' => 'GET',
                ),
                'read' => array(
                    'path' => '/{id}',
                    'method' => 'GET',
                ),
                'edit' => array(
                    'path' => '/{id}',
                    'method' => 'PUT',
                ),
                'add' => array(
                    'path' => '/',
                    'method' => 'POST',
                ),
                'delete' => array(
                    'path' => '/{id}',
                    'method' => 'DELETE',
                ),
            ),
            
            'require' => array(
                'id'            => '([0-9]+)',
            ),
            
            'default' => array(
                'controller' => 'resource',
            ),
            
            'name_prefix' => 'resource:',
        ));
        
        // fail to match
        $actual = $this->router->match('/foo/bar/baz/dib');
        $this->assertFalse($actual);
        
        // browse
        $server = array('REQUEST_METHOD' => 'GET');
        $actual = $this->router->match('/resource/', $server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('resource', $actual->params['controller']);
        $this->assertSame('browse', $actual->params['action']);
        $this->assertSame('resource:browse', $actual->name);
        
        // read
        $server = array('REQUEST_METHOD' => 'GET');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('resource:read', $actual->name);
        $expect_values = array(
            'controller' => 'resource',
            'action' => 'read',
            'id' => 42,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // edit
        $server = array('REQUEST_METHOD' => 'PUT');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('resource:edit', $actual->name);
        $expect_values = array(
            'controller' => 'resource',
            'action' => 'edit',
            'id' => 42,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // add
        $server = array('REQUEST_METHOD' => 'POST');
        $actual = $this->router->match('/resource/', $server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('resource', $actual->params['controller']);
        $this->assertSame('add', $actual->params['action']);
        $this->assertSame('resource:add', $actual->name);
        
        // delete
        $server = array('REQUEST_METHOD' => 'DELETE');
        $actual = $this->router->match('/resource/42', $server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('resource:delete', $actual->name);
        $expect_values = array(
            'controller' => 'resource',
            'action' => 'delete',
            'id' => 42,
        );
        $this->assertEquals($expect_values, $actual->params);
    }
    
    public function testAttachWithCallable()
    {
        $type = 'Aura\Router\Route';
        
        $this->router->attach('/page', function () {
            return array(
                'routes' => array(
                    'browse' => '/',
                    'read' => '/{id}{format}',
                    'edit' => '/{id}/edit',
                    'add' => '/add',
                    'delete' => '/{id}/delete',
                ),
                'require' => array(
                    'id'            => '([0-9]+)',
                    'format'        => '(\.[a-z0-9]+$)?',
                ),
                'default'     => array(
                    'controller' => 'page',
                    'format' => null,
                ),
                'name_prefix' => 'page:',
            );
        });
        
        // fail to match
        $actual = $this->router->match('/foo/bar/baz/dib');
        $this->assertFalse($actual);
        
        // browse
        $actual = $this->router->match('/page/');
        
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page', $actual->params['controller']);
        $this->assertSame('browse', $actual->params['action']);
        $this->assertSame('page:browse', $actual->name);
        
        // read
        $actual = $this->router->match('/page/42');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // read w/ format
        $actual = $this->router->match('/page/42.json');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => '.json',
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // edit
        $actual = $this->router->match('/page/42/edit');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:edit', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'edit',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // add
        $actual = $this->router->match('/page/add');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:add', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'add',
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // delete
        $actual = $this->router->match('/page/42/delete');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:delete', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'delete',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
    }
    
    /**
     * @todo Implement testGenerate().
     */
    public function testGenerate()
    {
        $this->router->attach('/page', array(
            'routes' => array(
                'browse' => '/',
                'read' => '/{id}{format}',
                'edit' => '/{id}/edit',
                'add' => '/add',
                'delete' => '/{id}/delete',
            ),
            'require' => array(
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'default'     => array(
                'controller' => 'page',
                'format' => null,
            ),
            'name_prefix' => 'page:',
        ));
        
        // get a named route
        $actual = $this->router->generate('page:read', array(
            'id' => 42,
            'format' => null,
        ));
        $this->assertSame('/page/42', $actual);
        
        // get the same one again, for code coverage of the portion that
        // looks up previously-generated route objects
        $actual = $this->router->generate('page:read', array(
            'id' => 84,
            'format' => null,
        ));
        $this->assertSame('/page/84', $actual);
        
        // fail to match again, for code coverage of the portion that checks
        // if there are definitions left to convert
        $this->setExpectedException('Aura\Router\Exception\RouteNotFound');
        $actual = $this->router->generate('no-route-again');
    }
    
    
    public function testGenerateWhenMissing()
    {
        $this->router->attach('/page', array(
            'routes' => array(
                'browse' => '/',
                'read' => '/{id}{format}',
                'edit' => '/{id}/edit',
                'add' => '/add',
                'delete' => '/{id}/delete',
            ),
            'require' => array(
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'default'     => array(
                'controller' => 'page',
                'format' => null,
            ),
            'name_prefix' => 'page:',
        ));
        
        // fail to match
        $this->setExpectedException('Aura\Router\Exception\RouteNotFound');
        $actual = $this->router->generate('no-route');
    }
    
    public function testAttachAtConstructionTime()
    {
        $type = 'Aura\Router\Route';
        
        $attach = array(
            '/page' => array(
                'routes' => array(
                    'browse' => '/',
                    'read' => '/{id}{format}',
                    'edit' => '/{id}/edit',
                    'add' => '/add',
                    'delete' => '/{id}/delete',
                ),
                'require' => array(
                    'id'            => '([0-9]+)',
                    'format'        => '(\.[a-z0-9]+$)?',
                ),
                'default'     => array(
                    'controller' => 'page',
                    'format' => null,
                ),
                'name_prefix' => 'page:',
            ),
        );
        
        $this->router = $this->newRouter($attach);
        
        /** SAME AS namedRoutesWithPrefixes */
        // fail to match
        $actual = $this->router->match('/foo/bar/baz/dib');
        $this->assertFalse($actual);
        
        // browse
        $actual = $this->router->match('/page/');
        
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page', $actual->params['controller']);
        $this->assertSame('browse', $actual->params['action']);
        $this->assertSame('page:browse', $actual->name);
        
        // read
        $actual = $this->router->match('/page/42');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // read w/ format
        $actual = $this->router->match('/page/42.json');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => '.json',
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // edit
        $actual = $this->router->match('/page/42/edit');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:edit', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'edit',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // add
        $actual = $this->router->match('/page/add');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:add', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'add',
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
        
        // delete
        $actual = $this->router->match('/page/42/delete');
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:delete', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'delete',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->params);
    }
    
    public function testRunOutOfAttachedRoutesToMatch()
    {
        $type = 'Aura\Router\Route';
        
        $attach = array(
            '/page' => array(
                'routes' => array(
                    'browse' => '/',
                    'read' => '/{id}{format}',
                    'edit' => '/{id}/edit',
                    'add' => '/add',
                    'delete' => '/{id}/delete',
                ),
                'require' => array(
                    'id'            => '([0-9]+)',
                    'format'        => '(\.[a-z0-9]+$)?',
                ),
                'default'     => array(
                    'controller' => 'page',
                    'format' => null,
                ),
                'name_prefix' => 'page:',
            ),
        );
        
        $this->router = $this->newRouter($attach);
        $this->router->add('home', '/');
        
        $actual = $this->router->match('/no/such/path');
        $this->assertFalse($actual);
    }
    
    public function testGetAndSetRoutes()
    {
        $this->router->attach('/page', array(
            'routes' => array(
                'browse' => '/',
                'read' => '/{id}{format}',
            ),
            'require' => array(
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'default'     => array(
                'controller' => 'page',
                'format' => null,
            ),
            'name_prefix' => 'page:',
        ));
        
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
