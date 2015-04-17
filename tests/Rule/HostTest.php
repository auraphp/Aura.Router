<?php
namespace Aura\Router\Rule;

class HostTest extends AbstractRuleTest
{
    public function setup()
    {
        parent::setup();
        $this->rule = new Host();
    }

    public function testIsMatchOnStaticHost()
    {
        $proto = $this->newRoute('/foo/bar/baz')->setHost('foo.example.com');

        // right host
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['HTTP_HOST' => 'foo.example.com']);
        $this->assertIsMatch($request, $route);

        // wrong host
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['HTTP_HOST' => 'bar.example.com']);
        $this->assertIsNotMatch($request, $route);
    }

    public function testIsMatchOnDynamicHost()
    {
        $proto = $this->newRoute('/foo/bar/baz')->setHost('{domain}?.?example.com');

        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['HTTP_HOST' => 'foo.example.com']);
        $this->assertIsMatch($request, $route);
        $this->assertEquals(['domain' => 'foo'], $route->attributes);

        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['HTTP_HOST' => 'bar.example.com']);
        $this->assertIsMatch($request, $route);
        $this->assertEquals(['domain' => 'bar'], $route->attributes);

        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['HTTP_HOST' => 'example.com']);
        $this->assertIsMatch($request, $route);
        $this->assertEquals(['domain' => null], $route->attributes);

        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['HTTP_HOST' => 'another.com']);
        $this->assertIsNotMatch($request, $route);
    }
}
