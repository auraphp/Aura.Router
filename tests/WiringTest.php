<?php
namespace Aura\Router;

use Aura\Framework\Test\WiringAssertionsTrait;

class WiringTest extends \PHPUnit_Framework_TestCase
{
    use WiringAssertionsTrait;

    protected function setUp()
    {
        $this->loadDi();
    }

    public function testServices()
    {
        $this->assertGet('router_map', 'Aura\Router\Map');
    }

    public function testInstances()
    {
        $this->assertNewInstance('Aura\Router\Map');
    }
}
