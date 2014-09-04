<?php
namespace Aura\Router\_Config;

use Aura\Di\ContainerAssertionsTrait;

class CommonTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAssertionsTrait;

    public function setUp()
    {
        $this->setUpContainer(array(
            'Aura\Router\_Config\Common',
        ));
    }

    public function test()
    {
        $this->assertNewInstance('Aura\Router\RouteCollection');
        $this->assertNewInstance('Aura\Router\Router');
    }
}
