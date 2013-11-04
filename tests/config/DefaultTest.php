<?php
namespace Aura\Router\Config;

use Aura\Framework\Test\WiringAssertionsTrait;

class DefaultTest extends \PHPUnit_Framework_TestCase
{
    use WiringAssertionsTrait;

    protected function setUp()
    {
        $this->loadDi();
    }

    public function testServices()
    {
        $this->assertGet('router', 'Aura\Router\Router');
    }

    public function testInstances()
    {
        $this->assertNewInstance('Aura\Router\Router');
    }
}
