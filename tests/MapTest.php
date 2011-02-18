<?php
namespace aura\router;

/**
 * Test class for Map.
 */
class MapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Map
     */
    protected $map;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->map = new Map(new RouteFactory);
        $this->server = $_SERVER;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testAddUnnamedRoute()
    {
        $this->map->add(null, '/foo/bar/baz');
        $actual = $this->map->getRoute('/foo/bar/baz', $this->server);
        $this->assertType('aura\router\Route', $actual);
        $this->assertSame('/foo/bar/baz', $actual->path);
    }
    
    public function testAddNamedRoute()
    {
        $this->map->add('zim', '/zim/dib/gir');
        $actual = $this->map->getRoute('/zim/dib/gir', $this->server);
        $this->assertType('aura\router\Route', $actual);
        $this->assertSame('/zim/dib/gir', $actual->path);
        $this->assertSame('zim', $actual->name);
    }
    
    public function testAddComplexRoute()
    {
        $this->map->add('read', '/resource/{:id}', array(
            'params' => array(
                'id' => '(\d+)',
            ),
            'values' => array(
                'controller' => 'foo',
                'action' => 'bar',
                'zim' => 'gir'
            ),
        ));
        
        $actual = $this->map->getRoute('/resource/42', $this->server);
        $this->assertType('aura\router\Route', $actual);
        $this->assertSame('foo', $actual->values['controller']);
        $this->assertSame('bar', $actual->values['action']);
        $this->assertSame('42', $actual->values['id']);
        $this->assertSame('gir', $actual->values['zim']);
    }
    
    /**
     * @expectedException \aura\router\Exception
     */
    public function testAttachWithoutRoutes()
    {
        $this->map->attach(null, array());
    }
    
    /**
     * @expectedException \aura\router\Exception
     */
    public function testAttachWithBadRouteSpec()
    {
        $this->map->attach(null, array(
            'routes' => array(
                'name' => 42,
            )
        ));
        
        $this->map->getRoute('/', $this->server);
    }
    
    public function testAttachRoutesWithoutPathPrefix()
    {
        $type = 'aura\router\Route';
        
        $this->map->attach(null, array(
            'routes' => array(
                '/{:controller}/{:action}/{:id}{:format}',
                '/{:controller}/{:action}/{:id}',
                '/{:controller}/{:action}',
                '/{:controller}',
                '/',
            ),
            'params' => array(
                'controller'    => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'action'        => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'values'     => array(
                'controller' => 'default_controller',
                'action'     => 'default_action',
                'format' => null,
            ),
        ));
        
        // fail to match
        $actual = $this->map->getRoute('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // path: /
        $actual = $this->map->getRoute('/', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('default_controller', $actual->values['controller']);
        $this->assertSame('default_action', $actual->values['action']);
        
        // path: /controller
        $actual = $this->map->getRoute('/foo', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('foo', $actual->values['controller']);
        $this->assertSame('default_action', $actual->values['action']);
        
        // path: /controller/action
        $actual = $this->map->getRoute('/foo/bar', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('foo', $actual->values['controller']);
        $this->assertSame('bar', $actual->values['action']);
        
        // path: /controller/action/id
        $actual = $this->map->getRoute('/foo/bar/42', $this->server);
        $expect_values = array(
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => null,
        );
        $this->assertType($type, $actual);
        $this->assertEquals($expect_values, $actual->values);
        
        // path: /controller/action/id.format
        $actual = $this->map->getRoute('/foo/bar/42.json', $this->server);
        $expect_values = array(
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => '.json',
        );
        $this->assertType($type, $actual);
        $this->assertEquals($expect_values, $actual->values);
    }
    
    public function testAttachNamedRoutes()
    {
        $type = 'aura\router\Route';
        
        $this->map->attach(null, array(
            'routes' => array(
                'browse' => '/',
                'read' => '/{:id}{:format}',
                'edit' => '/{:id}/edit',
                'add' => '/add',
                'delete' => '/{:id}/delete',
            ),
            'params' => array(
                'action'        => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'values'     => array(
                'controller' => 'page',
                'format' => null,
            ),
        ));
        
        // fail to match
        $actual = $this->map->getRoute('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // browse
        $actual = $this->map->getRoute('/', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('page', $actual->values['controller']);
        $this->assertSame('browse', $actual->values['action']);
        $this->assertSame('browse', $actual->name);
        
        // read
        $actual = $this->map->getRoute('/42', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // read w/ format
        $actual = $this->map->getRoute('/42.json', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => '.json',
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // edit
        $actual = $this->map->getRoute('/42/edit', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('edit', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'edit',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // add
        $actual = $this->map->getRoute('/add', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('page', $actual->values['controller']);
        $this->assertSame('add', $actual->values['action']);
        $this->assertSame('add', $actual->name);
        
        // delete
        $actual = $this->map->getRoute('/42/delete', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('delete', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'delete',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->values);
    }
    
    public function testAttachUnnamedLongFormRoutes()
    {
        $type = 'aura\router\Route';
        
        $this->map->attach(null, array(
            'routes' => array(
                array('path' => '/{:controller}/{:action}/{:id}{:format}',),
                array('path' => '/{:controller}/{:action}/{:id}',),
                array('path' => '/{:controller}/{:action}',),
                array('path' => '/{:controller}',),
                array('path' => '/',),
            ),
            'params' => array(
                'controller'    => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'action'        => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'values'     => array(
                'controller' => 'default_controller',
                'action'     => 'default_action',
                'format' => null,
            ),
        ));
        
        // fail to match
        $actual = $this->map->getRoute('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // path: /
        $actual = $this->map->getRoute('/', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('default_controller', $actual->values['controller']);
        $this->assertSame('default_action', $actual->values['action']);
        
        // path: /controller
        $actual = $this->map->getRoute('/foo', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('foo', $actual->values['controller']);
        $this->assertSame('default_action', $actual->values['action']);
        
        // path: /controller/action
        $actual = $this->map->getRoute('/foo/bar', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('foo', $actual->values['controller']);
        $this->assertSame('bar', $actual->values['action']);
        
        // path: /controller/action/id
        $actual = $this->map->getRoute('/foo/bar/42', $this->server);
        $this->assertType($type, $actual);
        $expect_values = array(
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // path: /controller/action/id.format
        $actual = $this->map->getRoute('/foo/bar/42.json', $this->server);
        $this->assertType($type, $actual);
        $expect_values = array(
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => '.json',
        );
        $this->assertEquals($expect_values, $actual->values);
    }
    
    public function testAttachNamedRoutesWithPrefixes()
    {
        $type = 'aura\router\Route';
        
        $this->map->attach('/page', array(
            'routes' => array(
                'browse' => '/',
                'read' => '/{:id}{:format}',
                'edit' => '/{:id}/edit',
                'add' => '/add',
                'delete' => '/{:id}/delete',
            ),
            'params' => array(
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'values'     => array(
                'controller' => 'page',
                'format' => null,
            ),
            'name_prefix' => 'page:',
        ));
        
        // fail to match
        $actual = $this->map->getRoute('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // browse
        $actual = $this->map->getRoute('/page/', $this->server);
        
        $this->assertType($type, $actual);
        $this->assertSame('page', $actual->values['controller']);
        $this->assertSame('browse', $actual->values['action']);
        $this->assertSame('page:browse', $actual->name);
        
        // read
        $actual = $this->map->getRoute('/page/42', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // read w/ format
        $actual = $this->map->getRoute('/page/42.json', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => '.json',
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // edit
        $actual = $this->map->getRoute('/page/42/edit', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('page:edit', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'edit',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // add
        $actual = $this->map->getRoute('/page/add', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('page:add', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'add',
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // delete
        $actual = $this->map->getRoute('/page/42/delete', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('page:delete', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'delete',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->values);
    }
    
    public function testAttachNamedRestRoutesWithPrefixes()
    {
        $type = 'aura\router\Route';
        
        $this->map->attach('/resource', array(
            'routes' => array(
                'browse' => array(
                    'path' => '/',
                    'method' => 'GET',
                ),
                'read' => array(
                    'path' => '/{:id}',
                    'method' => 'GET',
                ),
                'edit' => array(
                    'path' => '/{:id}',
                    'method' => 'PUT',
                ),
                'add' => array(
                    'path' => '/',
                    'method' => 'POST',
                ),
                'delete' => array(
                    'path' => '/{:id}',
                    'method' => 'DELETE',
                ),
            ),
            
            'params' => array(
                'id'            => '([0-9]+)',
            ),
            
            'values' => array(
                'controller' => 'resource',
            ),
            
            'name_prefix' => 'resource:',
        ));
        
        // fail to match
        $actual = $this->map->getRoute('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // browse
        $server = array('REQUEST_METHOD' => 'GET');
        $actual = $this->map->getRoute('/resource/', $server);
        $this->assertType($type, $actual);
        $this->assertSame('resource', $actual->values['controller']);
        $this->assertSame('browse', $actual->values['action']);
        $this->assertSame('resource:browse', $actual->name);
        
        // read
        $server = array('REQUEST_METHOD' => 'GET');
        $actual = $this->map->getRoute('/resource/42', $server);
        $this->assertType($type, $actual);
        $this->assertSame('resource:read', $actual->name);
        $expect_values = array(
            'controller' => 'resource',
            'action' => 'read',
            'id' => 42,
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // edit
        $server = array('REQUEST_METHOD' => 'PUT');
        $actual = $this->map->getRoute('/resource/42', $server);
        $this->assertType($type, $actual);
        $this->assertSame('resource:edit', $actual->name);
        $expect_values = array(
            'controller' => 'resource',
            'action' => 'edit',
            'id' => 42,
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // add
        $server = array('REQUEST_METHOD' => 'POST');
        $actual = $this->map->getRoute('/resource/', $server);
        $this->assertType($type, $actual);
        $this->assertSame('resource', $actual->values['controller']);
        $this->assertSame('add', $actual->values['action']);
        $this->assertSame('resource:add', $actual->name);
        
        // delete
        $server = array('REQUEST_METHOD' => 'DELETE');
        $actual = $this->map->getRoute('/resource/42', $server);
        $this->assertType($type, $actual);
        $this->assertSame('resource:delete', $actual->name);
        $expect_values = array(
            'controller' => 'resource',
            'action' => 'delete',
            'id' => 42,
        );
        $this->assertEquals($expect_values, $actual->values);
    }
    
    /**
     * @todo Implement testGetPath().
     */
    public function testGetPath()
    {
        $this->map->attach('/page', array(
            'routes' => array(
                'browse' => '/',
                'read' => '/{:id}{:format}',
                'edit' => '/{:id}/edit',
                'add' => '/add',
                'delete' => '/{:id}/delete',
            ),
            'params' => array(
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'values'     => array(
                'controller' => 'page',
                'format' => null,
            ),
            'name_prefix' => 'page:',
        ));
        
        // get a named route
        $actual = $this->map->getPath('page:read', array(
            'id' => 42,
            'format' => null,
        ));
        $this->assertSame('/page/42', $actual);
        
        // get the same one again, for code coverage of the portion that
        // looks up previously-generated route objects
        $actual = $this->map->getPath('page:read', array(
            'id' => 84,
            'format' => null,
        ));
        $this->assertSame('/page/84', $actual);
        
        // fail to match again, for code coverage of the portion that checks
        // if there are definitions left to convert
        $actual = $this->map->getPath('no-route-again');
        $this->assertFalse($actual);
    }
    
    
    public function testGetPathWhenMissing()
    {
        $this->map->attach('/page', array(
            'routes' => array(
                'browse' => '/',
                'read' => '/{:id}{:format}',
                'edit' => '/{:id}/edit',
                'add' => '/add',
                'delete' => '/{:id}/delete',
            ),
            'params' => array(
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'values'     => array(
                'controller' => 'page',
                'format' => null,
            ),
            'name_prefix' => 'page:',
        ));
        
        // fail to match
        $actual = $this->map->getPath('no-route');
        $this->assertFalse($actual);
    }
    
    public function testAttachAtConstructionTime()
    {
        $type = 'aura\router\Route';
        
        $attach = array(
            '/page' => array(
                'routes' => array(
                    'browse' => '/',
                    'read' => '/{:id}{:format}',
                    'edit' => '/{:id}/edit',
                    'add' => '/add',
                    'delete' => '/{:id}/delete',
                ),
                'params' => array(
                    'id'            => '([0-9]+)',
                    'format'        => '(\.[a-z0-9]+$)?',
                ),
                'values'     => array(
                    'controller' => 'page',
                    'format' => null,
                ),
                'name_prefix' => 'page:',
            ),
        );
        
        $this->map = new Map(new RouteFactory, $attach);
        
        /** SAME AS namedRoutesWithPrefixes */
        // fail to match
        $actual = $this->map->getRoute('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // browse
        $actual = $this->map->getRoute('/page/', $this->server);
        
        $this->assertType($type, $actual);
        $this->assertSame('page', $actual->values['controller']);
        $this->assertSame('browse', $actual->values['action']);
        $this->assertSame('page:browse', $actual->name);
        
        // read
        $actual = $this->map->getRoute('/page/42', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // read w/ format
        $actual = $this->map->getRoute('/page/42.json', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => '.json',
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // edit
        $actual = $this->map->getRoute('/page/42/edit', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('page:edit', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'edit',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // add
        $actual = $this->map->getRoute('/page/add', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('page:add', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'add',
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->values);
        
        // delete
        $actual = $this->map->getRoute('/page/42/delete', $this->server);
        $this->assertType($type, $actual);
        $this->assertSame('page:delete', $actual->name);
        $expect_values = array(
            'controller' => 'page',
            'action' => 'delete',
            'id' => 42,
            'format' => null,
        );
        $this->assertEquals($expect_values, $actual->values);
    }
    
    public function testRunOutOfAttachedRoutesToMatch()
    {
        $type = 'aura\router\Route';
        
        $attach = array(
            '/page' => array(
                'routes' => array(
                    'browse' => '/',
                    'read' => '/{:id}{:format}',
                    'edit' => '/{:id}/edit',
                    'add' => '/add',
                    'delete' => '/{:id}/delete',
                ),
                'params' => array(
                    'id'            => '([0-9]+)',
                    'format'        => '(\.[a-z0-9]+$)?',
                ),
                'values'     => array(
                    'controller' => 'page',
                    'format' => null,
                ),
                'name_prefix' => 'page:',
            ),
        );
        
        $this->map = new Map(new RouteFactory, $attach);
        $this->map->add('home', '/');
        
        $actual = $this->map->getRoute('/no/such/path', $this->server);
        $this->assertFalse($actual);
    }
    
    public function testGetAndSetRoutes()
    {
        $this->map->attach('/page', array(
            'routes' => array(
                'browse' => '/',
                'read' => '/{:id}{:format}',
            ),
            'params' => array(
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ),
            'values'     => array(
                'controller' => 'page',
                'format' => null,
            ),
            'name_prefix' => 'page:',
        ));
        
        $actual = $this->map->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertTrue(count($actual) == 2);
        $this->assertType('aura\router\Route', $actual['page:browse']);
        $this->assertEquals('/page/', $actual['page:browse']->path);
        $this->assertType('aura\router\Route', $actual['page:read']);
        $this->assertEquals('/page/{:id}{:format}', $actual['page:read']->path);
        
        // emulate caching the values
        $saved = serialize($actual);
        $restored = unserialize($saved);
        
        // set routes from the restored values
        $map = new Map(new RouteFactory);
        $map->setRoutes($restored);
        $actual = $map->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertTrue(count($actual) == 2);
        $this->assertType('aura\router\Route', $actual['page:browse']);
        $this->assertEquals('/page/', $actual['page:browse']->path);
        $this->assertType('aura\router\Route', $actual['page:read']);
        $this->assertEquals('/page/{:id}{:format}', $actual['page:read']->path);
        
    }
}
