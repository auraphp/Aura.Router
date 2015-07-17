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
 * A factory to create a router.
 *
 * @package Aura.Router
 *
 */
class RouterFactory
{
    /**
     *
     * A basepath to all routes.
     *
     * @var string
     *
     */
    protected $basepath;

    /**
     *
     * Constructor.
     *
     * @param string $basepath A basepath to to all routes.
     *
     */
    public function __construct($basepath = null)
    {
        $this->basepath = $basepath;
    }

    /**
     *
     * Returns a new Router instance.
     *
     * @return Router
     *
     */
    public function newInstance()
    {
        return new Router(
            new RouteCollection(new RouteFactory),
            new Generator,
            $this->basepath
        );
    }
}
