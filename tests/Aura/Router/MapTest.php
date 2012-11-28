<?php
namespace Aura\Router;

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
        $this->map = $this->newMap();
        $this->server = $_SERVER;
    }

    protected function newMap($attach = null)
    {
        return new Map(new DefinitionFactory, new RouteFactory, $attach);
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
        $actual = $this->map->match('/foo/bar/baz', $this->server);
        $this->assertInstanceOf('Aura\Router\Route', $actual);
        $this->assertSame('/foo/bar/baz', $actual->path);
    }
    
    public function testAddNamedRoute()
    {
        $this->map->add('zim', '/zim/dib/gir');
        $actual = $this->map->match('/zim/dib/gir', $this->server);
        $this->assertInstanceOf('Aura\Router\Route', $actual);
        $this->assertSame('/zim/dib/gir', $actual->path);
        $this->assertSame('zim', $actual->name);
    }
    
    public function testAddComplexRoute()
    {
        $this->map->add('read', '/resource/{:id}', [
            'params' => [
                'id' => '(\d+)',
            ],
            'values' => [
                'controller' => 'foo',
                'action' => 'bar',
                'zim' => 'gir'
            ],
        ]);
        
        $actual = $this->map->match('/resource/42', $this->server);
        $this->assertInstanceOf('Aura\Router\Route', $actual);
        $this->assertSame('foo', $actual->values['controller']);
        $this->assertSame('bar', $actual->values['action']);
        $this->assertSame('42', $actual->values['id']);
        $this->assertSame('gir', $actual->values['zim']);
    }
    
    /**
     * @expectedException \Aura\Router\Exception
     */
    public function testAttachWithBadRouteSpec()
    {
        $this->map->attach(null, [
            'routes' => [
                'name' => 42,
            ],
        ]);
        
        $this->map->match('/', $this->server);
    }
    
    public function testAttachRoutesWithoutPathPrefix()
    {
        $type = 'Aura\Router\Route';
        
        $this->map->attach(null, [
            'routes' => [
                '/{:controller}/{:action}/{:id}{:format}',
                '/{:controller}/{:action}/{:id}',
                '/{:controller}/{:action}',
                '/{:controller}',
                '/',
            ],
            'params' => [
                'controller'    => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'action'        => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ],
            'values'     => [
                'controller' => 'default_controller',
                'action'     => 'default_action',
                'format' => null,
            ],
        ]);
        
        // fail to match
        $actual = $this->map->match('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // path: /
        $actual = $this->map->match('/', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('default_controller', $actual->values['controller']);
        $this->assertSame('default_action', $actual->values['action']);
        
        // path: /controller
        $actual = $this->map->match('/foo', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('foo', $actual->values['controller']);
        $this->assertSame('default_action', $actual->values['action']);
        
        // path: /controller/action
        $actual = $this->map->match('/foo/bar', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('foo', $actual->values['controller']);
        $this->assertSame('bar', $actual->values['action']);
        
        // path: /controller/action/id
        $actual = $this->map->match('/foo/bar/42', $this->server);
        $expect_values = [
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => null,
        ];
        $this->assertInstanceOf($type, $actual);
        $this->assertEquals($expect_values, $actual->values);
        
        // path: /controller/action/id.format
        $actual = $this->map->match('/foo/bar/42.json', $this->server);
        $expect_values = [
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => '.json',
        ];
        $this->assertInstanceOf($type, $actual);
        $this->assertEquals($expect_values, $actual->values);
    }
    
    public function testAttachNamedRoutes()
    {
        $type = 'Aura\Router\Route';
        
        $this->map->attach(null, [
            'routes' => [
                'browse' => '/',
                'read' => '/{:id}{:format}',
                'edit' => '/{:id}/edit',
                'add' => '/add',
                'delete' => '/{:id}/delete',
            ],
            'params' => [
                'action'        => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ],
            'values'     => [
                'controller' => 'page',
                'format' => null,
            ],
        ]);
        
        // fail to match
        $actual = $this->map->match('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // browse
        $actual = $this->map->match('/', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page', $actual->values['controller']);
        $this->assertSame('browse', $actual->values['action']);
        $this->assertSame('browse', $actual->name);
        
        // read
        $actual = $this->map->match('/42', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('read', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // read w/ format
        $actual = $this->map->match('/42.json', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('read', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => '.json',
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // edit
        $actual = $this->map->match('/42/edit', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('edit', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'edit',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // add
        $actual = $this->map->match('/add', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page', $actual->values['controller']);
        $this->assertSame('add', $actual->values['action']);
        $this->assertSame('add', $actual->name);
        
        // delete
        $actual = $this->map->match('/42/delete', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('delete', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'delete',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
    }
    
    public function testAttachUnnamedLongFormRoutes()
    {
        $type = 'Aura\Router\Route';
        
        $this->map->attach(null, [
            'routes' => [
                ['path' => '/{:controller}/{:action}/{:id}{:format}'],
                ['path' => '/{:controller}/{:action}/{:id}'],
                ['path' => '/{:controller}/{:action}'],
                ['path' => '/{:controller}'],
                ['path' => '/'],
            ],
            'params' => [
                'controller'    => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'action'        => '([a-zA-Z][a-zA-Z0-9_-]*)',
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ],
            'values'     => [
                'controller' => 'default_controller',
                'action'     => 'default_action',
                'format' => null,
            ],
        ]);
        
        // fail to match
        $actual = $this->map->match('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // path: /
        $actual = $this->map->match('/', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('default_controller', $actual->values['controller']);
        $this->assertSame('default_action', $actual->values['action']);
        
        // path: /controller
        $actual = $this->map->match('/foo', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('foo', $actual->values['controller']);
        $this->assertSame('default_action', $actual->values['action']);
        
        // path: /controller/action
        $actual = $this->map->match('/foo/bar', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('foo', $actual->values['controller']);
        $this->assertSame('bar', $actual->values['action']);
        
        // path: /controller/action/id
        $actual = $this->map->match('/foo/bar/42', $this->server);
        $this->assertInstanceOf($type, $actual);
        $expect_values = [
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // path: /controller/action/id.format
        $actual = $this->map->match('/foo/bar/42.json', $this->server);
        $this->assertInstanceOf($type, $actual);
        $expect_values = [
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => '.json',
        ];
        $this->assertEquals($expect_values, $actual->values);
    }
    
    public function testAttachNamedRoutesWithPrefixes()
    {
        $type = 'Aura\Router\Route';
        
        $this->map->attach('/page', [
            'routes' => [
                'browse' => '/',
                'read' => '/{:id}{:format}',
                'edit' => '/{:id}/edit',
                'add' => '/add',
                'delete' => '/{:id}/delete',
            ],
            'params' => [
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ],
            'values'     => [
                'controller' => 'page',
                'format' => null,
            ],
            'name_prefix' => 'page:',
        ]);
        
        // fail to match
        $actual = $this->map->match('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // browse
        $actual = $this->map->match('/page/', $this->server);
        
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page', $actual->values['controller']);
        $this->assertSame('browse', $actual->values['action']);
        $this->assertSame('page:browse', $actual->name);
        
        // read
        $actual = $this->map->match('/page/42', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // read w/ format
        $actual = $this->map->match('/page/42.json', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => '.json',
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // edit
        $actual = $this->map->match('/page/42/edit', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:edit', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'edit',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // add
        $actual = $this->map->match('/page/add', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:add', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'add',
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // delete
        $actual = $this->map->match('/page/42/delete', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:delete', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'delete',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
    }
    
    public function testAttachNamedRestRoutesWithPrefixes()
    {
        $type = 'Aura\Router\Route';
        
        $this->map->attach('/resource', [
            'routes' => [
                'browse' => [
                    'path' => '/',
                    'method' => 'GET',
                ],
                'read' => [
                    'path' => '/{:id}',
                    'method' => 'GET',
                ],
                'edit' => [
                    'path' => '/{:id}',
                    'method' => 'PUT',
                ],
                'add' => [
                    'path' => '/',
                    'method' => 'POST',
                ],
                'delete' => [
                    'path' => '/{:id}',
                    'method' => 'DELETE',
                ],
            ],
            
            'params' => [
                'id'            => '([0-9]+)',
            ],
            
            'values' => [
                'controller' => 'resource',
            ],
            
            'name_prefix' => 'resource:',
        ]);
        
        // fail to match
        $actual = $this->map->match('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // browse
        $server = ['REQUEST_METHOD' => 'GET'];
        $actual = $this->map->match('/resource/', $server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('resource', $actual->values['controller']);
        $this->assertSame('browse', $actual->values['action']);
        $this->assertSame('resource:browse', $actual->name);
        
        // read
        $server = ['REQUEST_METHOD' => 'GET'];
        $actual = $this->map->match('/resource/42', $server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('resource:read', $actual->name);
        $expect_values = [
            'controller' => 'resource',
            'action' => 'read',
            'id' => 42,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // edit
        $server = ['REQUEST_METHOD' => 'PUT'];
        $actual = $this->map->match('/resource/42', $server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('resource:edit', $actual->name);
        $expect_values = [
            'controller' => 'resource',
            'action' => 'edit',
            'id' => 42,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // add
        $server = ['REQUEST_METHOD' => 'POST'];
        $actual = $this->map->match('/resource/', $server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('resource', $actual->values['controller']);
        $this->assertSame('add', $actual->values['action']);
        $this->assertSame('resource:add', $actual->name);
        
        // delete
        $server = ['REQUEST_METHOD' => 'DELETE'];
        $actual = $this->map->match('/resource/42', $server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('resource:delete', $actual->name);
        $expect_values = [
            'controller' => 'resource',
            'action' => 'delete',
            'id' => 42,
        ];
        $this->assertEquals($expect_values, $actual->values);
    }
    
    public function testAttachWithCallable()
    {
        $type = 'Aura\Router\Route';
        
        $this->map->attach('/page', function () {
            return [
                'routes' => [
                    'browse' => '/',
                    'read' => '/{:id}{:format}',
                    'edit' => '/{:id}/edit',
                    'add' => '/add',
                    'delete' => '/{:id}/delete',
                ],
                'params' => [
                    'id'            => '([0-9]+)',
                    'format'        => '(\.[a-z0-9]+$)?',
                ],
                'values'     => [
                    'controller' => 'page',
                    'format' => null,
                ],
                'name_prefix' => 'page:',
            ];
        });
        
        // fail to match
        $actual = $this->map->match('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // browse
        $actual = $this->map->match('/page/', $this->server);
        
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page', $actual->values['controller']);
        $this->assertSame('browse', $actual->values['action']);
        $this->assertSame('page:browse', $actual->name);
        
        // read
        $actual = $this->map->match('/page/42', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // read w/ format
        $actual = $this->map->match('/page/42.json', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => '.json',
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // edit
        $actual = $this->map->match('/page/42/edit', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:edit', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'edit',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // add
        $actual = $this->map->match('/page/add', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:add', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'add',
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // delete
        $actual = $this->map->match('/page/42/delete', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:delete', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'delete',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
    }
    
    /**
     * @todo Implement testGenerate().
     */
    public function testGenerate()
    {
        $this->map->attach('/page', [
            'routes' => [
                'browse' => '/',
                'read' => '/{:id}{:format}',
                'edit' => '/{:id}/edit',
                'add' => '/add',
                'delete' => '/{:id}/delete',
            ],
            'params' => [
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ],
            'values'     => [
                'controller' => 'page',
                'format' => null,
            ],
            'name_prefix' => 'page:',
        ]);
        
        // get a named route
        $actual = $this->map->generate('page:read', [
            'id' => 42,
            'format' => null,
        ]);
        $this->assertSame('/page/42', $actual);
        
        // get the same one again, for code coverage of the portion that
        // looks up previously-generated route objects
        $actual = $this->map->generate('page:read', [
            'id' => 84,
            'format' => null,
        ]);
        $this->assertSame('/page/84', $actual);
        
        // fail to match again, for code coverage of the portion that checks
        // if there are definitions left to convert
        $actual = $this->map->generate('no-route-again');
        $this->assertFalse($actual);
    }
    
    
    public function testGenerateWhenMissing()
    {
        $this->map->attach('/page', [
            'routes' => [
                'browse' => '/',
                'read' => '/{:id}{:format}',
                'edit' => '/{:id}/edit',
                'add' => '/add',
                'delete' => '/{:id}/delete',
            ],
            'params' => [
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ],
            'values'     => [
                'controller' => 'page',
                'format' => null,
            ],
            'name_prefix' => 'page:',
        ]);
        
        // fail to match
        $actual = $this->map->generate('no-route');
        $this->assertFalse($actual);
    }
    
    public function testAttachAtConstructionTime()
    {
        $type = 'Aura\Router\Route';
        
        $attach = [
            '/page' => [
                'routes' => [
                    'browse' => '/',
                    'read' => '/{:id}{:format}',
                    'edit' => '/{:id}/edit',
                    'add' => '/add',
                    'delete' => '/{:id}/delete',
                ],
                'params' => [
                    'id'            => '([0-9]+)',
                    'format'        => '(\.[a-z0-9]+$)?',
                ],
                'values'     => [
                    'controller' => 'page',
                    'format' => null,
                ],
                'name_prefix' => 'page:',
            ],
        ];
        
        $this->map = $this->newMap($attach);
        
        /** SAME AS namedRoutesWithPrefixes */
        // fail to match
        $actual = $this->map->match('/foo/bar/baz/dib', $this->server);
        $this->assertFalse($actual);
        
        // browse
        $actual = $this->map->match('/page/', $this->server);
        
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page', $actual->values['controller']);
        $this->assertSame('browse', $actual->values['action']);
        $this->assertSame('page:browse', $actual->name);
        
        // read
        $actual = $this->map->match('/page/42', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // read w/ format
        $actual = $this->map->match('/page/42.json', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:read', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'read',
            'id' => 42,
            'format' => '.json',
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // edit
        $actual = $this->map->match('/page/42/edit', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:edit', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'edit',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // add
        $actual = $this->map->match('/page/add', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:add', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'add',
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
        
        // delete
        $actual = $this->map->match('/page/42/delete', $this->server);
        $this->assertInstanceOf($type, $actual);
        $this->assertSame('page:delete', $actual->name);
        $expect_values = [
            'controller' => 'page',
            'action' => 'delete',
            'id' => 42,
            'format' => null,
        ];
        $this->assertEquals($expect_values, $actual->values);
    }
    
    public function testRunOutOfAttachedRoutesToMatch()
    {
        $type = 'Aura\Router\Route';
        
        $attach = [
            '/page' => [
                'routes' => [
                    'browse' => '/',
                    'read' => '/{:id}{:format}',
                    'edit' => '/{:id}/edit',
                    'add' => '/add',
                    'delete' => '/{:id}/delete',
                ],
                'params' => [
                    'id'            => '([0-9]+)',
                    'format'        => '(\.[a-z0-9]+$)?',
                ],
                'values'     => [
                    'controller' => 'page',
                    'format' => null,
                ],
                'name_prefix' => 'page:',
            ],
        ];
        
        $this->map = $this->newMap($attach);
        $this->map->add('home', '/');
        
        $actual = $this->map->match('/no/such/path', $this->server);
        $this->assertFalse($actual);
    }
    
    public function testGetAndSetRoutes()
    {
        $this->map->attach('/page', [
            'routes' => [
                'browse' => '/',
                'read' => '/{:id}{:format}',
            ],
            'params' => [
                'id'            => '([0-9]+)',
                'format'        => '(\.[a-z0-9]+$)?',
            ],
            'values'     => [
                'controller' => 'page',
                'format' => null,
            ],
            'name_prefix' => 'page:',
        ]);
        
        $actual = $this->map->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertTrue(count($actual) == 2);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page:browse']);
        $this->assertEquals('/page/', $actual['page:browse']->path);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page:read']);
        $this->assertEquals('/page/{:id}{:format}', $actual['page:read']->path);
        
        // emulate caching the values
        $saved = serialize($actual);
        $restored = unserialize($saved);
        
        // set routes from the restored values
        $map = $this->newMap();
        $map->setRoutes($restored);
        $actual = $map->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertTrue(count($actual) == 2);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page:browse']);
        $this->assertEquals('/page/', $actual['page:browse']->path);
        $this->assertInstanceOf('Aura\Router\Route', $actual['page:read']);
        $this->assertEquals('/page/{:id}{:format}', $actual['page:read']->path);
        
    }
    
    public function testGetLog()
    {
        // this is weak. we should actually see if the log contains anything.
        $this->assertSame([], $this->map->getLog());
    }
}
