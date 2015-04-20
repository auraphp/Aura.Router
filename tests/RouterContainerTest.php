<?php
namespace Aura\Router;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    protected $container;

    protected function setUp()
    {
        parent::setUp();
        $this->container = new RouterContainer();
    }

    public function test()
    {
        $route = new Route();
        $this->container->setProtoRoute($route);
        $this->assertSame($route, $this->container->getProtoRoute());

        $rules = ['foo'];
        $this->container->setRules($rules);
        $this->assertSame($rules, $this->container->getRules());
    }
}
