<?php
namespace Aura\Router\Helper;

use Aura\Router\Exception\RouteNotFound;
use Aura\Router\RouterContainer;

class UrlTest extends \PHPUnit_Framework_TestCase
{
    protected $helper;

    protected function setUp()
    {
        parent::setUp();
        $container = new RouterContainer();
        $map = $container->getMap();

        $map->route('blog.edit', '/blog/{id}/edit')
              ->tokens([
                  'id' => '([0-9]+)',
              ]);
        $map->route('blog', '/blog');

        $generator = $container->getGenerator();

        $this->helper = new Url($generator);
    }

    public function testInvokeReturnsGeneratedRoute()
    {
        $helper = $this->helper;
        $this->assertEquals('/blog/4%202/edit', $helper('blog.edit', ['id' => '4 2']));
    }

    public function testInvokeReturnsRaw()
    {
        $helper = $this->helper;
        $this->assertEquals('/blog/4 2/edit', $helper('blog.edit', ['id' => '4 2'], [], '', true));
    }

    public function testReturnQueryString()
    {
        $helper = $this->helper;
        $this->assertEquals('/blog?page=1', $helper('blog', [], ['page' => '1']));
    }

    public function testReturnWithFragment()
    {
        $helper = $this->helper;
        $this->assertEquals('/blog#heading', $helper('blog', [], [], 'heading'));
    }

    public function testReturnQueryStringWithFragment()
    {
        $helper = $this->helper;
        $this->assertEquals('/blog/42/edit?page=1#heading', $helper('blog.edit', ['id' => '42'], ['page' => 1], 'heading'));
    }

    public function testPassQueryStringReturnsQueryStringWithFragment()
    {
        $helper = $this->helper;
        $this->assertEquals('/blog/42/edit?page=1#heading', $helper('blog.edit', ['id' => '42'], '?page=1', 'heading'));
    }

    public function testPassObjectAsQueryParams()
    {
        $helper = $this->helper;
        $this->assertEquals('/blog/42/edit#heading', $helper('blog.edit', ['id' => '42'], new \stdClass(), 'heading'));
    }
}
