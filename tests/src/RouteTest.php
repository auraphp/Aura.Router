<?php
namespace Aura\Router;

use ArrayObject;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;

    protected $server;
    
    protected function setUp()
    {
        parent::setUp();
        $this->factory = new RouteFactory;
        $this->server = $_SERVER;
    }
    
    protected function tearDown()
    {
        parent::tearDown();
    }
    
    public function test__isset()
    {
        $route = $this->factory->newInstance('/foo/bar/baz')
            ->setValues(array(
                'controller' => 'zim',
                'action' => 'dib',
            ));
        
        $this->assertTrue(isset($route->path));
        $this->assertFalse(isset($route->no_such_property));
    }
    
    public function testIsMatchOnStaticPath()
    {
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setValues(array(
                'controller' => 'zim',
                'action' => 'dib',
            ));
        
        // right path
        $route = clone $proto;
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        $this->assertEquals('zim', $route->params['controller']);
        $this->assertEquals('dib', $route->params['action']);
        
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', $this->server));
    }
    
    public function testIsMatchOnDynamicPath()
    {
        $route = $this->factory->newInstance('/{controller}/{action}/{id}{format}')
            ->setTokens(array(
                'controller' => '([a-zA-Z][a-zA-Z0-9_-]+)',
                'action' => '([a-zA-Z][a-zA-Z0-9_-]+)',
                'id' => '([0-9]+)',
                'format' => '(\.[^/]+)?',
            ))
            ->setValues(array(
                'format' => '.html',
            ));
        
        $actual = $route->isMatch('/foo/bar/42', $this->server);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => '.html'
        );
        $this->assertEquals($expect, $route->params);
    }
    
    public function testIsServerMatch()
    {
        /**
         * try one REQUEST_METHOD
         */
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setServer(array(
                'REQUEST_METHOD' => 'POST',
            ));
    
        // correct
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'POST',
        )));
    
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'REQUEST_METHOD' => 'POST',
        )));
    
        // wrong REQUEST_METHOD
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'GET',
        )));
        
        /**
         * try many REQUEST_METHOD
         */
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setServer(array(
                'REQUEST_METHOD' => 'GET|POST',
            ));
    
        // correct
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'GET',
        )));
        
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'POST',
        )));
    
        // wrong path, right REQUEST_METHOD
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'REQUEST_METHOD' => 'GET',
        )));
        
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'REQUEST_METHOD' => 'POST',
        )));
        
        // right path, wrong REQUEST_METHOD
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'PUT',
        )));
        
        // no REQUEST_METHOD
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array()));
    }
    
    public function testIsSecureMatch_https()
    {
        /**
         * secure required
         */
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setSecure(true);
        
        // correct
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'HTTPS' => 'on',
        )));
        
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'HTTPS' => 'on',
        )));
        
        // not secure
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'HTTPS' => 'off',
        )));
        
        /**
         * not-secure required
         */
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setSecure(false);
        
        // correct
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'HTTPS' => 'off',
        )));
        
        // secured when it should not be
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'HTTPS' => 'on',
        )));
        
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'HTTPS' => 'off',
        )));
    }
    
    public function testIsSecureMatch_serverPort()
    {
        /**
         * secure required
         */
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setSecure(true);

        // correct
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'SERVER_PORT' => '443',
        )));
        
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'SERVER_PORT' => '443',
        )));
        
        // not secure
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'SERVER_PORT' => '80',
        )));
        
        /**
         * not-secure required
         */
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setSecure(false);
        
        // correct
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'SERVER_PORT' => '80',
        )));
        
        // secured when it should not be
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'SERVER_PORT' => '443',
        )));
        
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'SERVER_PORT' => '80',
        )));
    }
    
    public function testIsCustomMatchWithClosure()
    {
        $route = $this->factory->newInstance('/foo/bar/baz')
            ->setIsMatchCallable(function($server, ArrayObject $matches) {
                $matches['zim'] = 'gir';
                return true;
            });
        
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        $this->assertEquals('gir', $route->params['zim']);
        
        $route = $this->factory->newInstance('/foo/bar/baz')
            ->setIsMatchCallable(function($server, $matches) {
                return false;
            });
        
        // even though path is correct, should fail because of the closure
        $this->assertFalse($route->isMatch('/foo/bar/baz', $this->server));
    }
    
    public function testIsCustomMatchWithCallback()
    {
        $route = $this->factory->newInstance('/foo/bar/baz')
            ->setIsMatchCallable(array($this, 'callbackForIsMatchTrue'));
        
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        $this->assertEquals('gir', $route->params['zim']);
        
        $route = $this->factory->newInstance('/foo/bar/baz')
            ->setIsMatchCallable(array($this, 'callbackForIsMatchFalse'));
        
        // even though path is correct, should fail because of the closure
        $this->assertFalse($route->isMatch('/foo/bar/baz', $this->server));
    }
    
    public function callbackForIsMatchTrue(array $server, ArrayObject $matches)
    {
        $matches['zim'] = 'gir';
        return true;
    }
    
    public function callbackForIsMatchFalse(array $server, ArrayObject $matches)
    {
        return false;
    }
    
    /**
     * This test should not get exception for the urlencode on closure
     */
    public function testGenerateControllerAsClosureIssue19()
    {
        $route = $this->factory->newInstance('/blog/{id}/edit')
            ->setTokens(array(
                'id' => '([0-9]+)',
            ))
            ->setValues(array(
                "controller" => function ($params) {
                    $id = (int) $params['id'];
                    return "Hello World";
                },
                'action' => 'read',
                'format' => '.html',
            ));
        
        $url = $route->generate(array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/42/edit', $url);
    }
    
    public function testGenerate()
    {
        $route = $this->factory->newInstance('/blog/{id}/edit')
            ->setTokens(array(
                'id' => '([0-9]+)',
            ));
        
        $url = $route->generate(array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/42/edit', $url);
    }
    
    public function testGenerateWithClosure()
    {
        $route = $this->factory->newInstance('/blog/{id}/edit')
            ->setTokens(array(
                'id' => '([0-9]+)',
            ))
            ->setGenerateCallable(function(ArrayObject $data) {
                $data['id'] = 99;
            });
        
        $url = $route->generate(array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/99/edit', $url);
    }
    
    public function testGenerateWithCallback()
    {
        $route = $this->factory->newInstance('/blog/{id}/edit')
            ->setTokens(array(
                'id' => '([0-9]+)',
            ))
            ->setGenerateCallable(array($this, 'callbackForGenerate'));
        
        $url = $route->generate(array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/99/edit', $url);
    }
    
    public function testGenerateWithWildcard()
    {
        $route = $this->factory->newInstance('/blog/{id}')
            ->setTokens(array(
                'id' => '([0-9]+)',
            ))
            ->setWildcard('other');
        
        $url = $route->generate(array(
            'id' => 42,
            'foo' => 'bar',
            'other' => array(
                'dib' => 'zim',
                'irk' => 'gir',
            ),
        ));
            
        $this->assertEquals('/blog/42/zim/gir', $url);
    }
    
    public function testGenerateWithOptional()
    {
        $route = $this->factory->newInstance('/archive/{category}{/year,month,day}');
        
        $url = $route->generate(array(
            'category' => 'foo',
            'year' => '1979',
            'month' => '11',
        ));
        
        $this->assertEquals('/archive/foo/1979/11', $url);
    }
    
    public function callbackForGenerate(ArrayObject $data)
    {
        $data['id'] = 99;
    }
    
    public function testIsMatchOnDefaultAndDefinedSubpatterns()
    {
        $route = $this->factory->newInstance('/{controller}/{action}/{id}{format}')
            ->setTokens(array(
                'action' => '(browse|read|edit|add|delete)',
                'id' => '(\d+)',
                'format' => '(\.[^/]+)?',
            ));
        
        $actual = $route->isMatch('/any-value/read/42', $this->server);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'any-value',
            'action' => 'read',
            'id' => '42',
            'format' => null
        );
        $this->assertSame($expect, $route->params);
    }
    
    public function testIsNotRoutable()
    {
        $route = $this->factory->newInstance('/foo/bar/baz')
            ->setValues(array(
                'controller' => 'zim',
                'action' => 'dib',
            ))
            ->setRoutable(false);
        
        // right path
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertFalse($actual);
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', $this->server));
    }
    
    public function testGenerateOnFullUri()
    {
        $route = $this->factory->newInstance('http://google.com/?q={q}', 'google-search')
            ->setRoutable(false);
        
        $actual = $route->generate(array('q' => "what's up doc?"));
        $expect = "http://google.com/?q=what%27s%20up%20doc%3F";
        $this->assertSame($expect, $actual);
    }
   
    public function testGenerateRFC3986()
    {
        $route = $this->factory->newInstance('/path/{string}', 'rfc3986')
            ->setRoutable(false);
   
        // examples taken from http://php.net/manual/en/function.rawurlencode.php
        $actual = $route->generate(array('string' => 'foo @+%/'));
        $expect = '/path/foo%20%40%2B%25%2F';
        $this->assertSame($actual, $expect);
   
        $actual = $route->generate(array('string' => 'sales and marketing/Miami'));
        $expect = '/path/sales%20and%20marketing%2FMiami';
        $this->assertSame($actual, $expect);        
    }
   
    public function testIsMatchOnRFC3986Paths()
    {
        $route = $this->factory->newInstance('/{controller}/{action}/{param1}/{param2}');
        
        // examples taken from http://php.net/manual/en/function.rawurlencode.php
        $actual = $route->isMatch('/some-controller/some%20action/foo%20%40%2B%25%2F/sales%20and%20marketing%2FMiami', $this->server);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'some-controller',
            'action' => 'some action',
            'param1' => 'foo @+%/',
            'param2' => 'sales and marketing/Miami',
        );
        $this->assertEquals($expect, $route->params);
    }
   
   public function testGithubIssue7()
   {
        $server = array(
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
        );
        
        $route = $this->factory->newInstance('/blog/read/{id}{format}')
            ->setTokens(array(
                'id' => '(\d+)',
                'format' => '(\.json|\.html)?',
            ))
            ->setValues(array(
                'controller' => 'blog',
                'action' => 'read',
                'format' => '.html',
            ));
         
        $actual = $route->isMatch('/blog/read/42.json', $server);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'blog',
            'action' => 'read',
            'id' => 42,
            'format' => '.json'
        );
        $this->assertEquals($expect, $route->params);
    }
    
    public function testIsMatchOnlWildcard()
    {
        $proto = $this->factory->newInstance('/foo/{zim}/')
            ->setWildcard('wild');
        
        // right path with wildcard values
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz/dib', $this->server));
        $this->assertSame('bar', $route->params['zim']);
        $this->assertSame(array('baz', 'dib'), $route->params['wild']);
        
        // right path with trailing slash but no wildcard values
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/', $this->server));
        $this->assertSame('bar', $route->params['zim']);
        $this->assertSame(array(), $route->params['wild']);
        
        // right path without trailing slash
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar', $this->server));
        $this->assertSame(array(), $route->params['wild']);
        
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', $this->server));
    }
    
    public function testIsMatchOnOptionalParams()
    {
        $route = $this->factory->newInstance('/foo/{bar}{/baz,dib,zim}');
        
        // not enough params
        $actual = $route->isMatch('/foo', $this->server);
        $this->assertFalse($actual);
        
        // just enough params
        $actual = $route->isMatch('/foo/bar', $this->server);
        $this->assertTrue($actual);
        
        // optional param 1
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        
        // optional param 2
        $actual = $route->isMatch('/foo/bar/baz/dib', $this->server);
        $this->assertTrue($actual);
        
        // optional param 3
        $actual = $route->isMatch('/foo/bar/baz/dib/zim', $this->server);
        $this->assertTrue($actual);
        
        // too many params
        $actual = $route->isMatch('/foo/bar/baz/dib/zim/gir', $this->server);
        $this->assertFalse($actual);
    }
    
    public function testCaptureServerParams()
    {
        $route = $this->factory->newInstance('/foo')
            ->setServer(array(
                'HTTP_ACCEPT' => '(application/xml(;q=(1\.0|0\.[1-9]))?)|(application/json(;q=(1\.0|0\.[1-9]))?)',
            ));
        
        $server = array('HTTP_ACCEPT' => 'application/json;q=0.9,text/csv;q=0.5,application/xml;q=0.7');
        $actual = $route->isMatch('/foo', $server);
        $this->assertTrue($actual);
        
        $actual = $route->params;
        $expect = array(
            'HTTP_ACCEPT' => 'application/json;q=0.9',
        );
        $this->assertEquals($expect, $actual);
    }
    
    public function testIsMatchOnOnlyOptionalParams()
    {
        $route = $this->factory->newInstance('{/foo,bar,baz}');
        
        $actual = $route->isMatch('/', $this->server);
        $this->assertTrue($actual);
        
        $actual = $route->isMatch('/foo', $this->server);
        $this->assertTrue($actual);
        
        $actual = $route->isMatch('/foo/bar', $this->server);
        $this->assertTrue($actual);
        
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
    }

    public function testOptionalTokens() {
        $foo = $this->factory->newInstance('[/{language}]/blog/archive[/{year}][/{month}][.{type}]')
            ->addTokens(array(
                'language' => '[a-z]{0,2}',
                'year' => '\d+',
                'month' => '\d+',
            ))
            ->addValues(array(
                'language' => 'en',
                'type' => 'html'
            ));

        $this->assertTrue($foo->isMatch('/fr/blog/archive/2014/01', array()));
        $this->assertTrue($foo->isMatch('/blog/archive/2014/01', array()));

        $this->assertEquals(
            array(
                'language' => 'en',
                'type' => 'html',
                'year' => '2014',
                'month' => '01',
            ),
            $foo->params);

        // test generate with default language
        $url = $foo->generate(array('languge' => 'en', 'type' => 'xml', 'year' => 2014));
        $this->assertEquals('/blog/archive/2014.xml', $url);

        // test generate with default type
        $url = $foo->generate(array('language' => 'fr', 'type' => 'html', 'year' => 2014));
        $this->assertEquals('/fr/blog/archive/2014', $url);
    }
}
