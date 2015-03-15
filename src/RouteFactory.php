<?php
/**
 *
 * This file is part of the Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

/**
 *
 * A factory to create Route objects.
 *
 * @package Aura.Router
 *
 */
class RouteFactory
{
    /**
     *
     * The route class to create.
     *
     * @param string
     *
     */
    protected $class = 'Aura\Router\Route';

    /**
     *
     * A reusable Regex object.
     *
     * @param Regex
     *
     */
    protected $regex;

    /**
     *
     * The default route specification.
     *
     * @var array
     *
     */
    protected $spec = array(
        'tokens' => array(),
        'server' => array(),
        'method' => array(),
        'accept' => array(),
        'values' => array(),
        'secure' => null,
        'wildcard' => null,
        'routable' => true,
        'is_match' => null,
        'generate' => null,
        'name_prefix' => null,
        'path_prefix' => null,
    );

    /**
     *
     * Constructor.
     *
     * @param string $class The route class to create.
     *
     */
    public function __construct($class = 'Aura\Router\Route')
    {
        $this->class = $class;
        $this->regex = new Regex;
    }

    /**
     *
     * Returns a new instance of the route class.
     *
     * @param string $path The path for the route.
     *
     * @param string $name The name for the route.
     *
     * @param array $spec The spec for the new instance.
     *
     * @return Route
     *
     */
    public function newInstance($path, $name = null, array $spec = array())
    {
        $spec = array_merge($this->spec, $spec);

        $path = $spec['path_prefix'] . $path;

        $name = ($spec['name_prefix'] && $name)
              ? $spec['name_prefix'] . '.' . $name
              : $name;

        $class = $this->class;
        $route = new $class($this->regex, $path, $name);
        $route->addTokens($spec['tokens']);
        $route->addServer($spec['server']);
        $route->addMethod($spec['method']);
        $route->addAccept($spec['accept']);
        $route->addValues($spec['values']);
        $route->setSecure($spec['secure']);
        $route->setWildcard($spec['wildcard']);
        $route->setRoutable($spec['routable']);
        $route->setIsMatchCallable($spec['is_match']);
        $route->setGenerateCallable($spec['generate']);
        return $route;
    }
}
