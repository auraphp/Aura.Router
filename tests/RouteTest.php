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
}
