<?php
namespace Aura\Router;

/**
 * Test class for Route.
 */
class RouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RouteFactory
     */
    protected $factory;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->factory = new RouteFactory;
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
    
    public function testIsMatchOnStaticPath()
    {
        $route = $this->factory->newInstance([
            'path' => '/foo/bar/baz',
            'values' => [
                'controller' => 'zim',
                'action' => 'dib',
            ],
        ]);
        
        // right path
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        $this->assertEquals('zim', $route->values['controller']);
        $this->assertEquals('dib', $route->values['action']);
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', $this->server));
    }
    
    public function testIsMatchOnDynamicPath()
    {
        $route = $this->factory->newInstance([
            'path' => '/{:controller}/{:action}/{:id}{:format}',
            'params' => [
                'controller' => '([a-zA-Z][a-zA-Z0-9_-]+)',
                'action' => '([a-zA-Z][a-zA-Z0-9_-]+)',
                'id' => '([0-9]+)',
                'format' => '(\.[a-zA-Z0-9]$)?'
            ],
            'values' => [
                'format' => '.html',
            ],
        ]);
        
        $actual = $route->isMatch('/foo/bar/42', $this->server);
        $this->assertTrue($actual);
        $expect = [
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => '.html'
        ];
        $this->assertEquals($expect, $route->values);
    }
    
    public function testIsMethodMatch()
    {
        $type = 'Aura\Router\Route';
    
        /**
         * try one method
         */
        $route = $this->factory->newInstance([
            'path' => '/foo/bar/baz',
            'method' => 'POST',
        ]);
    
        // correct
        $this->assertTrue($route->isMatch('/foo/bar/baz', [
            'REQUEST_METHOD' => 'POST',
        ]));
    
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', [
            'REQUEST_METHOD' => 'POST',
        ]));
    
        // wrong method
        $this->assertFalse($route->isMatch('/foo/bar/baz', [
            'REQUEST_METHOD' => 'GET',
        ]));
        
        /**
         * try many methods
         */
        $route = $this->factory->newInstance([
            'path' => '/foo/bar/baz',
            'method' => ['GET', 'POST'],
        ]);
    
        // correct
        $this->assertTrue($route->isMatch('/foo/bar/baz', [
            'REQUEST_METHOD' => 'GET',
        ]));
        
        $this->assertTrue($route->isMatch('/foo/bar/baz', [
            'REQUEST_METHOD' => 'POST',
        ]));
    
        // wrong path, right methods
        $this->assertFalse($route->isMatch('/zim/dib/gir', [
            'REQUEST_METHOD' => 'GET',
        ]));
        
        $this->assertFalse($route->isMatch('/zim/dib/gir', [
            'REQUEST_METHOD' => 'POST',
        ]));
        
        // right path, wrong method
        $this->assertFalse($route->isMatch('/foo/bar/baz', [
            'REQUEST_METHOD' => 'PUT',
        ]));
        
        // no request method
        $this->assertFalse($route->isMatch('/foo/bar/baz', []));
    }
    
    public function testIsSecureMatch_https()
    {
        $type = 'Aura\Router\Route';
        
        /**
         * secure required
         */
        $route = $this->factory->newInstance([
            'path' => '/foo/bar/baz',
            'secure' => true,
        ]);
        
        // correct
        $this->assertTrue($route->isMatch('/foo/bar/baz', [
            'HTTPS' => 'on',
        ]));
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', [
            'HTTPS' => 'on',
        ]));
        
        // not secure
        $this->assertFalse($route->isMatch('/foo/bar/baz', [
            'HTTPS' => 'off',
        ]));
        
        /**
         * not-secure required
         */
        $route = $this->factory->newInstance([
            'path' => '/foo/bar/baz',
            'secure' => false,
        ]);
        
        // correct
        $this->assertTrue($route->isMatch('/foo/bar/baz', [
            'HTTPS' => 'off',
        ]));
        
        // secured when it should not be
        $this->assertFalse($route->isMatch('/foo/bar/baz', [
            'HTTPS' => 'on',
        ]));
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', [
            'HTTPS' => 'off',
        ]));
    }
    
    public function testIsSecureMatch_serverPort()
    {
        $type = 'Aura\Router\Route';
        
        /**
         * secure required
         */
        $route = $this->factory->newInstance([
            'path' => '/foo/bar/baz',
            'secure' => true,
        ]);
        
        // correct
        $this->assertTrue($route->isMatch('/foo/bar/baz', [
            'SERVER_PORT' => '443',
        ]));
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', [
            'SERVER_PORT' => '443',
        ]));
        
        // not secure
        $this->assertFalse($route->isMatch('/foo/bar/baz', [
            'SERVER_PORT' => '80',
        ]));
        
        /**
         * not-secure required
         */
        $route = $this->factory->newInstance([
            'path' => '/foo/bar/baz',
            'secure' => false,
        ]);
        
        // correct
        $this->assertTrue($route->isMatch('/foo/bar/baz', [
            'SERVER_PORT' => '80',
        ]));
        
        // secured when it should not be
        $this->assertFalse($route->isMatch('/foo/bar/baz', [
            'SERVER_PORT' => '443',
        ]));
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', [
            'SERVER_PORT' => '80',
        ]));
    }
    
    public function testIsCustomMatchWithClosure()
    {
        $type = 'Aura\Router\Route';
        
        $route = $this->factory->newInstance([
            'path' => '/foo/bar/baz',
            'is_match' => function($server, &$matches) {
                $matches['zim'] = 'gir';
                return true;
            },
        ]);
        
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        $this->assertEquals('gir', $route->values['zim']);
        
        $route = $this->factory->newInstance([
            'path' => '/foo/bar/baz',
            'is_match' => function($server, $matches) {
                return false;
            },
        ]);
        
        // even though path is correct, should fail because of the closure
        $this->assertFalse($route->isMatch('/foo/bar/baz', $this->server));
    }
    
    public function testIsCustomMatchWithCallback()
    {
        $type = 'Aura\Router\Route';
        
        $route = $this->factory->newInstance([
            'path' => '/foo/bar/baz',
            'is_match' => [$this, 'callbackForIsMatchTrue'],
        ]);
        
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        $this->assertEquals('gir', $route->values['zim']);
        
        $route = $this->factory->newInstance([
            'path' => '/foo/bar/baz',
            'is_match' => [$this, 'callbackForIsMatchFalse'],
        ]);
        
        // even though path is correct, should fail because of the closure
        $this->assertFalse($route->isMatch('/foo/bar/baz', $this->server));
    }
    
    public function callbackForIsMatchTrue(array $server, \ArrayObject $matches)
    {
        $matches['zim'] = 'gir';
        return true;
    }
    
    public function callbackForIsMatchFalse(array $server, \ArrayObject $matches)
    {
        return false;
    }
    
    /**
     * @expectedException \Aura\Router\Exception
     */
    public function testBadSubpattern()
    {
        $route = $this->factory->newInstance([
            'path' => '/{:controller}',
            'params' => [
                // should open with a paren
                'controller' => '[a-zA-Z][a-zA-Z0-9_-]+',
            ],
        ]);
    }
    
    public function testGenerate()
    {
        $route = $this->factory->newInstance([
          'path' => '/blog/{:id}/edit',
          'params' => [
              'id' => '([0-9]+)',
          ],
        ]);
        
        $uri = $route->generate(['id' => 42, 'foo' => 'bar']);
        $this->assertEquals('/blog/42/edit', $uri);
    }
    
    public function testGenerateWithClosure()
    {
        $route = $this->factory->newInstance([
          'path' => '/blog/{:id}/edit',
          'params' => [
              'id' => '([0-9]+)',
          ],
          'generate' => function($route, $data) {
              $data['id'] = 99;
              return $data;
          }
        ]);
        
        $uri = $route->generate(['id' => 42, 'foo' => 'bar']);
        $this->assertEquals('/blog/99/edit', $uri);
    }
    
    public function testGenerateWithCallback()
    {
        $route = $this->factory->newInstance([
          'path' => '/blog/{:id}/edit',
          'params' => [
              'id' => '([0-9]+)',
          ],
          'generate' => [$this, 'callbackForGenerate'],
        ]);
        
        $uri = $route->generate(['id' => 42, 'foo' => 'bar']);
        $this->assertEquals('/blog/99/edit', $uri);
    }
    
    public function callbackForGenerate(\Aura\Router\Route $route, array $data)
    {
        $data['id'] = 99;
        return $data;
    }
    
    public function testIsMatchOnDefaultAndInlineSubpatterns()
    {
        $route = $this->factory->newInstance([
            'path' => '/{:controller}/{:action:(browse|read|edit|add|delete)}/{:id:(\d+)}{:format:(\..*)?}',
        ]);
        
        $actual = $route->isMatch('/any-value/read/42', $this->server);
        $this->assertTrue($actual);
        $expect = [
            'controller' => 'any-value',
            'action' => 'read',
            'id' => 42,
        ];
        $this->assertEquals($expect, $route->values);
    }
    
    public function testIsNotRoutable()
    {
        $route = $this->factory->newInstance([
            'path' => '/foo/bar/baz',
            'values' => [
                'controller' => 'zim',
                'action' => 'dib',
            ],
            'routable' => false,
        ]);
        
        // right path
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertFalse($actual);
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', $this->server));
    }
    
    public function testGenerateOnFullUri()
    {
        $route = $this->factory->newInstance([
            'name' => 'google-search',
            'path' => 'http://google.com/?q={:q}',
            'routable' => false,
            'path_prefix' => '/foo/bar', // SHOULD NOT show up
        ]);
        
        $actual = $route->generate(['q' => "what's up doc?"]);
        $expect = "http://google.com/?q=what%27s+up+doc%3F";
        $this->assertSame($expect, $actual);
    }

   public function testGithubIssue7()
   {
        $server = [
            'DOCUMENT_ROOT' => '/media/Linux/auracomponentstest',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 49850,
            'SERVER_SOFTWARE' => 'PHP 5.4.0RC5-dev Development Server',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 8000,
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => '/media/Linux/auracomponentstest/index.php',
            'PHP_SELF' => '/index.php',
            'HTTP_HOST' => 'localhost:8000',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.1 (KHTML, like Gecko) Ubuntu/11.10 Chromium/14.0.835.202 Chrome/14.0.835.202 Safari/535.1',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_ENCODING' => 'gzip,deflate,sdch',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'REQUEST_TIME' => '1327369518.2441',
        ];
        
        $route = $this->factory->newInstance([
            'path' => '/blog/read/{:id}{:format}',
            'params' => [
                'id' => '(\d+)',
                'format' => '(\.json|\.html)?',
            ],
            'values' => [
                'controller' => 'blog',
                'action' => 'read',
                'format' => '.html',
            ],
        ]);
         
        $actual = $route->isMatch('/blog/read/42.json', $server);
        $this->assertTrue($actual);
        $expect = [
            'controller' => 'blog',
            'action' => 'read',
            'id' => 42,
            'format' => '.json'
        ];
        $this->assertEquals($expect, $route->values);
   }
   
   public function testIsMatchOnWildcard()
   {
        $route = $this->factory->newInstance([
            'path' => '/foo/{:zim}/*',
        ]);
        
        // right path
        $actual = $route->isMatch('/foo/bar/baz/dib', $this->server);
        $this->assertTrue($actual);
        $this->assertSame('bar', $route->values['zim']);
        $this->assertSame(['baz', 'dib'], $route->values['*']);
        
        // right path, but no wildcard values. note the trailing slash.
        $actual = $route->isMatch('/foo/bar/', $this->server);
        $this->assertTrue($actual);
        $this->assertSame('bar', $route->values['zim']);
        $this->assertSame([], $route->values['*']);
        
        // right path, *but* no trailing slash. doesn't match.
        $this->assertFalse($route->isMatch('/foo/bar', $this->server));
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', $this->server));
   }
}
