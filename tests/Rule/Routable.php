<?php
namespace Aura\Router\Rule;

class RoutableTest extends AbstractRuleTest
{
    public function setup()
    {
        parent::setup();
        $this->rule = new Routable();
    }

    public function testIsNotRoutable()
    {
        $route = $this->newRoute('/foo/bar/baz')
            ->setDefaults(array(
                'controller' => 'zim',
                'action' => 'dib',
            ))
            ->setRoutable(false);

        $request = $this->newRequest('/foo/bar/baz');
        $actual = $route->isMatch($request);
        $this->assertFalse($actual);
    }
}
