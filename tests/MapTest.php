<?php
namespace Aura\Router;

class MapTest extends \PHPUnit_Framework_TestCase
{
    protected $map;

    protected function setUp()
    {
        parent::setUp();
        $container = new RouterContainer();
        $this->map = $container->getMap();
    }

    protected function assertIsRoute($actual)
    {
        $this->assertInstanceof('Aura\Router\Route', $actual);
    }

    protected function assertRoute($expect, $actual)
    {
        $this->assertIsRoute($actual);
        foreach ($expect as $key => $val) {
            $this->assertSame($val, $actual->$key);
        }
    }

    public function testRouteAlreadyExists()
    {
        $this->map->route('/foo', 'foo');
        $this->setExpectedException('Aura\Router\Exception\RouteAlreadyExists');
        $this->map->route('/foo', 'foo');
    }

    public function testBeforeAndAfterAttach()
    {
        $this->map->route('/foo', 'before');

        $this->map->attach('/during', 'during.', function ($map) {
            $map->setTokens(array('id' => '\d+'));
            $map->setMethod('GET');
            $map->setDefaults(array('zim' => 'gir'));
            $map->setSecure(true);
            $map->setWildcard('other');
            $map->setRoutable(false);
            $map->route('/bar', 'bar');
        });

        $this->map->route('/baz', 'after');

        $map = $this->map->getRoutes();

        $expect = array(
            'tokens' => array(),
            'server' => array(),
            'method' => array(),
            'defaults' => array(),
            'secure' => null,
            'wildcard' => null,
            'routable' => true,
        );
        $this->assertRoute($expect, $map['before']);
        $this->assertRoute($expect, $map['after']);

        $actual = $map['during.bar'];
        $expect = array(
            'tokens' => array('id' => '\d+'),
            'method' => array('GET'),
            'defaults' => array('zim' => 'gir'),
            'secure' => true,
            'wildcard' => 'other',
            'routable' => false,
        );
        $this->assertRoute($expect, $actual);
    }

    public function testAttachInAttach()
    {
        $this->map->attach('/foo', 'foo.', function ($map) {
            $map->route('/index', 'index');
            $map->attach('/bar', 'bar.', function ($map) {
                $map->route('/index', 'index');
            });
        });

        $map = $this->map->getRoutes();

        $this->assertSame('/foo/index', $map['foo.index']->path);
        $this->assertSame('/foo/bar/index', $map['foo.bar.index']->path);
    }

    public function testGetAndSetRoutes()
    {
        $this->map->attach('/page', 'page.', function ($map) {
            $map->setTokens(array(
                'id'            => '(\d+)',
                'format'        => '(\.[^/]+)?',
            ));

            $map->setDefaults(array(
                'controller' => 'page',
                'format' => null,
            ));

            $map->route('/', 'browse');
            $map->route('/{id}{format}', 'read');
        });

        $actual = $this->map->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertCount(2, $actual);
        $this->assertIsRoute($actual['page.browse']);
        $this->assertEquals('/page/', $actual['page.browse']->path);
        $this->assertIsRoute($actual['page.read']);
        $this->assertEquals('/page/{id}{format}', $actual['page.read']->path);

        // emulate caching the values
        $saved = serialize($actual);
        $restored = unserialize($saved);

        // set routes in new map from the restored values
        $container = new RouterContainer();
        $map = $container->getMap();
        $map->setRoutes($restored);
        $actual = $map->getRoutes();
        $this->assertTrue(is_array($actual));
        $this->assertCount(2, $actual);
        $this->assertIsRoute($actual['page.browse']);
        $this->assertEquals('/page/', $actual['page.browse']->path);
        $this->assertIsRoute($actual['page.read']);
        $this->assertEquals('/page/{id}{format}', $actual['page.read']->path);
    }

    public function testAddWithAction()
    {
        $this->map->route('/foo/bar', 'foo.bar', ['action' => 'DirectAction']);
        $actual = $this->map->getRoute('foo.bar');
        $this->assertSame('DirectAction', $actual->defaults['action']);
    }
}
