<?php
namespace Aura\Router\Rule;

class AcceptTest extends AbstractMatcherTest
{
    public function setup()
    {
        parent::setup();
        $this->matcher = new Accept();
    }

    public function testIsAcceptMatch()
    {
        $proto = $this->newRoute('/foo/bar/baz');

        // match when no HTTP_ACCEPT
        $route = clone $proto;
        $route->addAccept(array('zim/gir'));
        $request = $this->newRequest('/foo/bar/baz');
        $this->assertIsMatch($request, $route);

        // match */*
        $route = clone $proto;
        $route->addAccept(array('zim/gir'));
        $server = array('HTTP_ACCEPT' => 'text/*;q=0.9,application/json,*/*;q=0.1,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $this->assertIsMatch($request, $route);

        // do not match */* when q=0.0
        $route = clone $proto;
        $route->setAccept(array('zim/gir'));
        $server = array('HTTP_ACCEPT' => 'text/*;q=0.9,application/json,*/*;q=0.0,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $this->assertIsNotMatch($request, $route);

        // match text/csv
        $route = clone $proto;
        $route->setAccept(array('text/csv'));
        $server = array('HTTP_ACCEPT' => 'text/csv;q=0.9,application/json,*/*;q=0.0,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $this->assertIsMatch($request, $route);

        // do not match text/csv when q=0
        $route = clone $proto;
        $route->setAccept(array('text/csv'));
        $server = array('HTTP_ACCEPT' => 'application/json,text/csv;q=0.0,*/*;q=0.0,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $this->assertIsNotMatch($request, $route);

        // match text/*
        $route = clone $proto;
        $route->setAccept(array('text/csv'));
        $server = array('HTTP_ACCEPT' => 'application/json,text/*;q=0.9,*/*;q=0.1,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $this->assertIsMatch($request, $route);

        // do not match text/* when q=0
        $route = clone $proto;
        $route->setAccept(array('text/csv'));
        $server = array('HTTP_ACCEPT' => 'application/json,text/*;q=0.0,*/*;q=0.1,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $this->assertIsMatch($request, $route);

        // match application/json without q score
        $route = clone $proto;
        $route->setAccept(array('application/json'));
        $server = array('HTTP_ACCEPT' => 'application/json,text/*;q=0.0,*/*;q=0.1,application/xml');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $this->assertIsMatch($request, $route);

        // match application/json in simplest case
        $route = clone $proto;
        $route->setAccept(array('application/json'));
        $server = array('HTTP_ACCEPT' => 'application/json');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $this->assertIsMatch($request, $route);

        // do not match application/json in simplest case
        $route = clone $proto;
        $route->setAccept(array('application/json'));
        $server = array('HTTP_ACCEPT' => 'text/html');
        $request = $this->newRequest('/foo/bar/baz', $server);
        $this->assertIsNotMatch($request, $route);
    }
}
