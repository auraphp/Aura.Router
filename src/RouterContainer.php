<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

/**
 *
 * A library-specific container.
 *
 * @package Aura.Router
 *
 */
class RouterContainer
{
    protected $map;

    public function getMap()
    {
        if (! $this->map) {
            $this->map = new Map($this->newRouteFactory());
        }
    }

    public function newRouteFactory()
    {
        return new RouteFactory();
    }

    public function newMatcher()
    {
        return new Matcher($this->getMap());
    }

    public function newGenerator()
    {
        return new Generator($this->getMap());
    }
}
