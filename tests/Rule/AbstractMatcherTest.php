<?php
namespace Aura\Router\Rule;

use Aura\Router\RouteFactory;
use Phly\Http\ServerRequestFactory;

abstract class AbstractMatcherTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;

    protected $server;

    protected $matcher;

    protected function setUp()
    {
        parent::setUp();
        $this->routeFactory = new RouteFactory();
    }

    protected function newRequest($path, array $server = [])
    {
        $server['REQUEST_URI'] = $path;
        $server = array_merge($_SERVER, $server);
        return ServerRequestFactory::fromGlobals($server);
    }

    protected function newRoute($path)
    {
        return $this->routeFactory->newInstance($path, 'test');
    }

    protected function assertIsMatch($request, $route)
    {
        $this->assertTrue($this->matcher->__invoke($request, $route));
    }

    protected function assertIsNotMatch($request, $route)
    {
        $this->assertFalse($this->matcher->__invoke($request, $route));
    }
}
