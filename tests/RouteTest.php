<?php
namespace Aura\Router;

use Phly\Http\ServerRequestFactory;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;

    protected $server;

    protected function setUp()
    {
        parent::setUp();
        $this->factory = new RouteFactory();
        $this->server = $_SERVER;
    }

    protected function newRequest($path, array $server = [])
    {
        $server['REQUEST_URI'] = $path;
        $server = array_merge($_SERVER, $server);
        return ServerRequestFactory::fromGlobals($server);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function test__isset()
    {
        $route = $this->factory->newInstance('/foo/bar/baz')
            ->setDefaults(array(
                'controller' => 'zim',
                'action' => 'dib',
            ));

        $this->assertTrue(isset($route->path));
        $this->assertFalse(isset($route->no_such_property));
    }

    public function testIsMatchOnStaticPath()
    {
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setDefaults(array(
                'controller' => 'zim',
                'action' => 'dib',
            ));

        // right path
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);
        $this->assertEquals('zim', $route->attributes['controller']);
        $this->assertEquals('dib', $route->attributes['action']);

        // wrong path
        $route = clone $proto;
        $request = $this->newRequest('/zim/dib/gir');
        $this->assertFalse($route->isMatch($request));
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
            ->setDefaults(array(
                'format' => '.html',
            ));

        $request = $this->newRequest('/foo/bar/42');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => '.html'
        );
        $this->assertEquals($expect, $route->attributes);
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
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'POST']);
        $this->assertTrue($route->isMatch($request));

        // wrong path
        $route = clone $proto;
        $request = $this->newRequest('/zim/dib/gir', ['REQUEST_METHOD' => 'POST']);
        $this->assertFalse($route->isMatch($request));

        // wrong REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'GET']);
        $this->assertFalse($route->isMatch($request));

        /**
         * try many REQUEST_METHOD
         */
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setServer(array(
                'REQUEST_METHOD' => 'GET|POST',
            ));

        // correct
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'GET']);
        $this->assertTrue($route->isMatch($request));

        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'POST']);
        $this->assertTrue($route->isMatch($request));

        // wrong path, right REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/zim/dib/gir', ['REQUEST_METHOD' => 'GET']);
        $this->assertFalse($route->isMatch($request));

        $route = clone $proto;
        $request = $this->newRequest('/zim/dib/gir', ['REQUEST_METHOD' => 'POST']);
        $this->assertFalse($route->isMatch($request));

        // right path, wrong REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'PUT']);
        $this->assertFalse($route->isMatch($request));

        // no REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', []);
        $this->assertFalse($route->isMatch($request));
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
        $request = $this->newRequest('/foo/bar/baz', ['HTTPS' => 'on']);
        $this->assertTrue($route->isMatch($request));

        // wrong path
        $route = clone $proto;
        $request = $this->newRequest('/zim/dib/gir', ['HTTPS' => 'on']);
        $this->assertFalse($route->isMatch($request));

        // not secure
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['HTTPS' => 'off']);
        $this->assertFalse($route->isMatch($request));

        /**
         * not-secure required
         */
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setSecure(false);

        // correct
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['HTTPS' => 'off']);
        $this->assertTrue($route->isMatch($request));

        // secured when it should not be
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['HTTPS' => 'on']);
        $this->assertFalse($route->isMatch($request));

        // wrong path
        $route = clone $proto;
        $request = $this->newRequest('/zim/dib/gir', ['HTTPS' => 'off']);
        $this->assertFalse($route->isMatch($request));
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
        $request = $this->newRequest('/foo/bar/baz', ['SERVER_PORT' => '443']);
        $this->assertTrue($route->isMatch($request));

        // wrong path
        $route = clone $proto;
        $request = $this->newRequest('/zim/dib/gir', ['SERVER_PORT' => '443']);
        $this->assertFalse($route->isMatch($request));

        // not secure
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['SERVER_PORT' => '80']);
        $this->assertFalse($route->isMatch($request));

        /**
         * not-secure required
         */
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setSecure(false);

        // correct
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['SERVER_PORT' => '80']);
        $this->assertTrue($route->isMatch($request));

        // secured when it should not be
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['SERVER_PORT' => '443']);
        $this->assertFalse($route->isMatch($request));

        // wrong path
        $route = clone $proto;
        $request = $this->newRequest('/zim/dib/gir', ['SERVER_PORT' => '80']);
        $this->assertFalse($route->isMatch($request));
    }

    public function testIsMatchOnDefaultAndDefinedSubpatterns()
    {
        $route = $this->factory->newInstance('/{controller}/{action}/{id}{format}')
            ->setTokens(array(
                'action' => '(browse|read|edit|add|delete)',
                'id' => '(\d+)',
                'format' => '(\.[^/]+)?',
            ));

        $request = $this->newRequest('/any-value/read/42');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'any-value',
            'action' => 'read',
            'id' => '42',
            'format' => null
        );
        $this->assertSame($expect, $route->attributes);
    }

    public function testIsNotRoutable()
    {
        $route = $this->factory->newInstance('/foo/bar/baz')
            ->setDefaults(array(
                'controller' => 'zim',
                'action' => 'dib',
            ))
            ->setRoutable(false);

        // right path
        $request = $this->newRequest('/foo/bar/baz');
        $actual = $route->isMatch($request);
        $this->assertFalse($actual);

        // wrong path
        $request = $this->newRequest('/zim/dib/gir');
        $this->assertFalse($route->isMatch($request));
    }

    public function testIsMatchOnRFC3986Paths()
    {
        $route = $this->factory->newInstance('/{controller}/{action}/{attribute1}/{attribute2}');

        // examples taken from http://php.net/manual/en/function.rawurlencode.php
        $request = $this->newRequest('/some-controller/some%20action/foo%20%40%2B%25%2F/sales%20and%20marketing%2FMiami');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'some-controller',
            'action' => 'some action',
            'attribute1' => 'foo @+%/',
            'attribute2' => 'sales and marketing/Miami',
        );
        $this->assertEquals($expect, $route->attributes);
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
            ->setDefaults(array(
                'controller' => 'blog',
                'action' => 'read',
                'format' => '.html',
            ));


        $request = $this->newRequest('/blog/read/42.json', $server);
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'blog',
            'action' => 'read',
            'id' => 42,
            'format' => '.json'
        );
        $this->assertEquals($expect, $route->attributes);
    }

    public function testIsMatchOnlWildcard()
    {
        $proto = $this->factory->newInstance('/foo/{zim}/')
            ->setWildcard('wild');

        // right path with wildcard values
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz/dib');
        $this->assertTrue($route->isMatch($request));
        $this->assertSame('bar', $route->attributes['zim']);
        $this->assertSame(array('baz', 'dib'), $route->attributes['wild']);

        // right path with trailing slash but no wildcard values
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/');
        $this->assertTrue($route->isMatch($request));
        $this->assertSame('bar', $route->attributes['zim']);
        $this->assertSame(array(), $route->attributes['wild']);

        // right path without trailing slash
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar');
        $this->assertTrue($route->isMatch($request));
        $this->assertSame(array(), $route->attributes['wild']);

        // wrong path
        $route = clone $proto;
        $request = $this->newRequest('/zim/dib/gir');
        $this->assertFalse($route->isMatch($request));
    }

    public function testIsMatchOnOptionalAttributes()
    {
        $route = $this->factory->newInstance('/foo/{bar}{/baz,dib,zim}');

        // not enough attributes
        $request = $this->newRequest('/foo');
        $actual = $route->isMatch($request);
        $this->assertFalse($actual);

        // just enough attributes
        $request = $this->newRequest('/foo/bar');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        // optional attribute 1
        $request = $this->newRequest('/foo/bar/baz');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        // optional attribute 2
        $request = $this->newRequest('/foo/bar/baz/dib');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        // optional attribute 3
        $request = $this->newRequest('/foo/bar/baz/dib/zim');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        // too many attributes
        $request = $this->newRequest('/foo/bar/baz/dib/zim/gir');
        $actual = $route->isMatch($request);
        $this->assertFalse($actual);
    }

    public function testCaptureServerAttributes()
    {
        $route = $this->factory->newInstance('/foo')
            ->setServer(array(
                'HTTP_ACCEPT' => '(application/xml(;q=(1\.0|0\.[1-9]))?)|(application/json(;q=(1\.0|0\.[1-9]))?)',
            ));

        $server = array('HTTP_ACCEPT' => 'application/json;q=0.9,text/csv;q=0.5,application/xml;q=0.7');

        $request = $this->newRequest('/foo', $server);
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        $actual = $route->attributes;
        $expect = array(
            'HTTP_ACCEPT' => 'application/json;q=0.9',
        );
        $this->assertEquals($expect, $actual);
    }

    public function testIsMatchOnOnlyOptionalAttributes()
    {
        $route = $this->factory->newInstance('{/foo,bar,baz}');

        $request = $this->newRequest('/');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        $request = $this->newRequest('/foo');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        $request = $this->newRequest('/foo/bar');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        $request = $this->newRequest('/foo/bar/baz');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);
    }

    public function testIsMethodMatch()
    {
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setMethod('POST');

        // correct
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', array('REQUEST_METHOD' => 'POST'));
        $this->assertTrue($route->isMatch($request));

        // wrong path
        $route = clone $proto;
        $request = $this->newRequest('/zim/dib/gir', ['REQUEST_METHOD' => 'POST']);
        $this->assertFalse($route->isMatch($request));

        // wrong REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'GET']);
        $this->assertFalse($route->isMatch($request));
        $this->assertTrue($route->failedMethod());

        /**
         * try many REQUEST_METHOD
         */
        $proto = $this->factory->newInstance('/foo/bar/baz')
            ->setMethod(array('GET', 'POST'));

        // correct
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'GET']);
        $this->assertTrue($route->isMatch($request));

        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'POST']);
        $this->assertTrue($route->isMatch($request));

        // wrong path, right REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/zim/dib/gir', ['REQUEST_METHOD' => 'GET']);
        $this->assertFalse($route->isMatch($request));

        $route = clone $proto;
        $request = $this->newRequest('/zim/dib/gir', ['REQUEST_METHOD' => 'POST']);
        $this->assertFalse($route->isMatch($request));

        // right path, wrong REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'PUT']);
        $this->assertFalse($route->isMatch($request));
        $this->assertTrue($route->failedMethod());

        // no REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', []);
        $this->assertFalse($route->isMatch($request));
    }

    public function testIsAcceptMatch()
    {
        $proto = $this->factory->newInstance('/foo/bar/baz');

        // match when no HTTP_ACCEPT
        $route = clone $proto;
        $route->addAccept(array('zim/gir'));
        $request = $this->newRequest('/foo/bar/baz');
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        // match */*
        $route = clone $proto;
        $route->addAccept(array('zim/gir'));
        $server = array('HTTP_ACCEPT' => 'text/*;q=0.9,application/json,*/*;q=0.1,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        // do not match */* when q=0.0
        $route = clone $proto;
        $route->setAccept(array('zim/gir'));
        $server = array('HTTP_ACCEPT' => 'text/*;q=0.9,application/json,*/*;q=0.0,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $actual = $route->isMatch($request);
        $this->assertFalse($actual);
        $this->assertTrue($route->failedAccept());

        // match text/csv
        $route = clone $proto;
        $route->setAccept(array('text/csv'));
        $server = array('HTTP_ACCEPT' => 'text/csv;q=0.9,application/json,*/*;q=0.0,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        // do not match text/csv when q=0
        $route = clone $proto;
        $route->setAccept(array('text/csv'));
        $server = array('HTTP_ACCEPT' => 'application/json,text/csv;q=0.0,*/*;q=0.0,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $actual = $route->isMatch($request);
        $this->assertFalse($actual);
        $this->assertTrue($route->failedAccept());

        // match text/*
        $route = clone $proto;
        $route->setAccept(array('text/csv'));
        $server = array('HTTP_ACCEPT' => 'application/json,text/*;q=0.9,*/*;q=0.1,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        // do not match text/* when q=0
        $route = clone $proto;
        $route->setAccept(array('text/csv'));
        $server = array('HTTP_ACCEPT' => 'application/json,text/*;q=0.0,*/*;q=0.1,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        // match application/json without q score
        $route = clone $proto;
        $route->setAccept(array('application/json'));
        $server = array('HTTP_ACCEPT' => 'application/json,text/*;q=0.0,*/*;q=0.1,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        // match application/json in simplest case
        $route = clone $proto;
        $route->setAccept(array('application/json'));
        $server = array('HTTP_ACCEPT' => 'application/json');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $actual = $route->isMatch($request);
        $this->assertTrue($actual);

        // do not match application/json in simplest case
        $route = clone $proto;
        $route->setAccept(array('application/json'));
        $server = array('HTTP_ACCEPT' => 'text/html');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $actual = $route->isMatch($request);
        $this->assertFalse($actual);
    }

    public function testIsMatchOnOptionalEndingSlash()
    {
        $route = $this->factory->newInstance('/foo(/)?');
        $request = $this->newRequest('/foo', []);
        $this->assertTrue($route->isMatch($request));
        $request = $this->newRequest('/foo/', []);
        $this->assertTrue($route->isMatch($request));
    }
}
