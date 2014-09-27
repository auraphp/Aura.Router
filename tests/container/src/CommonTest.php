<?php
namespace Aura\Router\_Config;

use Aura\Di\_Config\AbstractContainerTest;

class CommonTest extends AbstractContainerTest
{
    protected function getConfigClasses()
    {
        return array(
            'Aura\Router\_Config\Common',
        );
    }

    public function provideNewInstance()
    {
        return array(
            array('Aura\Router\RouteCollection'),
            array('Aura\Router\Router'),
        );
    }
}
