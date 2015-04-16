<?php
namespace Aura\Router\Rule;

use Aura\Router\Route;
use Phly\Http\ServerRequestFactory;

abstract class AbstractRuleTest extends \PHPUnit_Framework_TestCase
{
    protected $rule;

    protected function newRequest($path, array $server = [])
    {
        $server['REQUEST_URI'] = $path;
        $server = array_merge($_SERVER, $server);
        return ServerRequestFactory::fromGlobals($server);
    }

    protected function newRoute($path)
    {
        $route = new Route();
        return $route->setPath($path);
    }

    protected function assertIsMatch($request, $route)
    {
        $this->assertTrue($this->rule->__invoke($request, $route));
    }

    protected function assertIsNotMatch($request, $route)
    {
        $this->assertFalse($this->rule->__invoke($request, $route));
    }
}
