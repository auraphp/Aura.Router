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
            ->defaults(array(
                'controller' => 'zim',
                'action' => 'dib',
            ))
            ->routable(false);

        $request = $this->newRequest('/foo/bar/baz');
        $this->assertIsNotMatch($request, $route);
    }
}
