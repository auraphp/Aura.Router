<?php
namespace Aura\Router\Rule;

class ServerTest extends AbstractRuleTest
{
    public function setup()
    {
        parent::setup();
        $this->rule = new Server();
    }

    public function testIsServerMatch()
    {
        /**
         * try one REQUEST_METHOD
         */
        $proto = $this->newRoute('/foo/bar/baz')
            ->setServer(array(
                'REQUEST_METHOD' => 'POST',
            ));

        // correct
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'POST']);
        $this->assertIsMatch($request, $route);

        // wrong REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'GET']);
        $this->assertIsNotMatch($request, $route);

        /**
         * try many REQUEST_METHOD
         */
        $proto = $this->newRoute('/foo/bar/baz')
            ->setServer(array(
                'REQUEST_METHOD' => 'GET|POST',
            ));

        // correct
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'GET']);
        $this->assertIsMatch($request, $route);

        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'POST']);
        $this->assertIsMatch($request, $route);

        // right path, wrong REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'PUT']);
        $this->assertIsNotMatch($request, $route);

        // no REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', []);
        $this->assertIsNotMatch($request, $route);
    }

    public function testCaptureServerAttributes()
    {
        $route = $this->newRoute('/foo')
            ->setServer(array(
                'HTTP_ACCEPT' => '(application/xml(;q=(1\.0|0\.[1-9]))?)|(application/json(;q=(1\.0|0\.[1-9]))?)',
            ));

        $server = array('HTTP_ACCEPT' => 'application/json;q=0.9,text/csv;q=0.5,application/xml;q=0.7');

        $request = $this->newRequest('/foo', $server);
        $this->assertIsMatch($request, $route);

        $actual = $route->attributes;
        $expect = array(
            'HTTP_ACCEPT' => 'application/json;q=0.9',
        );
        $this->assertEquals($expect, $actual);
    }

}
