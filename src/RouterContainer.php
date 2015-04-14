<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

use Psr\Log\NullLogger;

/**
 *
 * A library-specific container.
 *
 * @package Aura.Router
 *
 */
class RouterContainer
{
    protected $generator;
    protected $logger;
    protected $loggerFactory;
    protected $matcher;
    protected $map;
    protected $routeFactory;

    public function __construct()
    {
        $this->loggerFactory = function () { return new NullLogger(); };
        $this->routeFactory = function () { return new RouteFactory(); };
    }

    public function setLoggerFactory(callable $loggerFactory)
    {
        $this->loggerFactory = $loggerFactory;
    }

    public function setRouteFactory(callable $routeFactory)
    {
        $this->routeFactory = $routeFactory;
    }

    public function getMap()
    {
        if (! $this->map) {
            $this->map = new Map(call_user_func($this->routeFactory));
        }
        return $this->map;
    }

    public function getMatcher()
    {
        if (! $this->matcher) {
            $this->matcher = new Matcher($this->getMap(), $this->getLogger());
        }
        return $this->matcher;
    }

    public function getGenerator()
    {
        if (! $this->generator) {
            $this->generator = new Generator($this->getMap());
        }
        return $this->generator;
    }

    public function getLogger()
    {
        if (! $this->logger) {
            $this->logger = call_user_func($this->loggerFactory);
        }
        return $this->logger;
    }
}
