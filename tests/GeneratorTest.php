<?php
namespace Aura\Router;

use ArrayObject;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;
    protected $server;
    protected $generator;

    protected function setUp()
    {
        parent::setUp();
        $this->generator = new Generator;
        $this->factory = new RouteFactory;
        $this->server = $_SERVER;
    }

    /**
     * This test should not get exception for the urlencode on closure
     */
    public function testGenerateControllerAsClosureIssue19()
    {
        $route = $this->factory->newInstance('/blog/{id}/edit')
            ->setTokens(array(
                'id' => '([0-9]+)',
            ))
            ->setValues(array(
                "controller" => function ($params) {
                    $id = (int) $params['id'];
                    return "Hello World";
                },
                'action' => 'read',
                'format' => '.html',
            ));

        $url = $this->generator->generate($route, array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/42/edit', $url);
    }

    public function testGenerate()
    {
        $route = $this->factory->newInstance('/blog/{id}/edit')
            ->setTokens(array(
                'id' => '([0-9]+)',
            ));

        $url = $this->generator->generate($route, array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/42/edit', $url);
    }

    public function testGenerateWithClosure()
    {
        $route = $this->factory->newInstance('/blog/{id}/edit')
            ->setTokens(array(
                'id' => '([0-9]+)',
            ))
            ->setGenerateCallable(function(ArrayObject $data) {
                $data['id'] = 99;
            });

        $url = $this->generator->generate($route, array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/99/edit', $url);
    }

    public function testGenerateWithCallback()
    {
        $route = $this->factory->newInstance('/blog/{id}/edit')
            ->setTokens(array(
                'id' => '([0-9]+)',
            ))
            ->setGenerateCallable(array($this, 'callbackForGenerate'));

        $url = $this->generator->generate($route, array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/99/edit', $url);
    }

    public function testGenerateWithWildcard()
    {
        $route = $this->factory->newInstance('/blog/{id}')
            ->setTokens(array(
                'id' => '([0-9]+)',
            ))
            ->setWildcard('other');

        $url = $this->generator->generate($route, array(
            'id' => 42,
            'foo' => 'bar',
            'other' => array(
                'dib' => 'zim',
                'irk' => 'gir',
            ),
        ));

        $this->assertEquals('/blog/42/zim/gir', $url);
    }

    public function testGenerateWithOptional()
    {
        $route = $this->factory->newInstance('/archive/{category}{/year,month,day}');

        $url = $this->generator->generate($route, array(
            'category' => 'foo',
            'year' => '1979',
            'month' => '11',
        ));

        $this->assertEquals('/archive/foo/1979/11', $url);
    }

    public function callbackForGenerate(ArrayObject $data)
    {
        $data['id'] = 99;
    }

    public function testGenerateOnFullUri()
    {
        $route = $this->factory->newInstance('http://google.com/?q={q}', 'google-search')
            ->setRoutable(false);

        $actual = $this->generator->generate($route, array('q' => "what's up doc?"));
        $expect = "http://google.com/?q=what%27s%20up%20doc%3F";
        $this->assertSame($expect, $actual);
    }

    public function testGenerateRFC3986()
    {
        $route = $this->factory->newInstance('/path/{string}', 'rfc3986')
            ->setRoutable(false);

        // examples taken from http://php.net/manual/en/function.rawurlencode.php
        $actual = $this->generator->generate($route, array('string' => 'foo @+%/'));
        $expect = '/path/foo%20%40%2B%25%2F';
        $this->assertSame($actual, $expect);

        $actual = $this->generator->generate($route, array('string' => 'sales and marketing/Miami'));
        $expect = '/path/sales%20and%20marketing%2FMiami';
        $this->assertSame($actual, $expect);
    }

    public function testGenerateRaw()
    {
        $route = $this->factory->newInstance('/{vendor}/{package}/{file}');
        $data = array(
            'vendor' => 'vendor+name',
            'package' => 'package+name',
            'file' => 'foo/bar/baz.jpg',
        );
        $raw = array('file');
        $actual = $this->generator->generateRaw($route, $data);
        $expect = '/vendor+name/package+name/foo/bar/baz.jpg';
        $this->assertSame($actual, $expect);
    }
}
