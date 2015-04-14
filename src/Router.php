<?php
/**
 *
 * This file is part of the Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

use Aura\Router\Exception;

/**
 *
 * A wrapper for the collection of routes to be matched.
 *
 * @package Aura.Router
 *
 */
class Router
{
    /**
     *
     * Route objects created from the definitions.
     *
     * @var RouteCollection
     *
     */
    protected $routes;

    /**
     *
     * Constructor.
     *
     * @param RouteCollection $routes A route collection object.
     *
     */
    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }
}
