<?php
namespace Aura\Router;

use Phly\Http\ServerRequestFactory;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    public function test__get()
    {
        $route = new Route();
        $route->setPath('/foo/bar/baz');
        $this->assertSame('/foo/bar/baz', $route->path);
    }

    public function testImmutablePath()
    {
        $route = new Route();
        $route->setPath('/foo');
        $this->setExpectedException(
            'Aura\Router\Exception\ImmutableProperty',
            'Aura\Router\Route::$path'
        );
        $route->setPath('/bar');
    }

    public function testImmutablePathPrefix()
    {
        $route = new Route();
        $route->setPath('/foo');
        $this->setExpectedException(
            'Aura\Router\Exception\ImmutableProperty',
            'Aura\Router\Route::$pathPrefix'
        );
        $route->appendPathPrefix('/bar');
    }

    public function testImmutableName()
    {
        $route = new Route();
        $route->setName('/foo');
        $this->setExpectedException(
            'Aura\Router\Exception\ImmutableProperty',
            'Aura\Router\Route::$name'
        );
        $route->setName('/bar');
    }

    public function testImmutableNamePrefix()
    {
        $route = new Route();
        $route->setName('/foo');
        $this->setExpectedException(
            'Aura\Router\Exception\ImmutableProperty',
            'Aura\Router\Route::$namePrefix'
        );
        $route->appendNamePrefix('/bar');
    }
}
