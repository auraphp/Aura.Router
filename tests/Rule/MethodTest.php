<?php
namespace Aura\Router\Rule;

class MethodTest extends AbstractRuleTest
{
    public function setup()
    {
        parent::setup();
        $this->rule = new Method();
    }

    public function testIsMethodMatch()
    {
        $proto = $this->newRoute('/foo/bar/baz')
            ->allows('POST');

        // correct
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', array('REQUEST_METHOD' => 'POST'));
        $this->assertIsMatch($request, $route);

        // wrong REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'GET']);
        $this->assertIsNotMatch($request, $route);

        /**
         * try many REQUEST_METHOD
         */
        $proto = $this->newRoute('/foo/bar/baz')
            ->allows(array('GET', 'POST'));

        // correct
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'GET']);
        $this->assertIsMatch($request, $route);

        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'POST']);
        $this->assertIsMatch($request, $route);

        // right path, wrong REQUEST_METHOD
        $route = clone $proto;
        $request = $this->newRequest('/foo/bar/baz', ['REQUEST_METHOD' => 'PUT']);
        $this->assertIsNotMatch($request, $route);
    }
}
