<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

use Aura\Router\Rule\RuleIterator;
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
    protected $ruleFactories = [];
    protected $ruleIterator;
    protected $protoRoute;

    public function __construct()
    {
        $this->loggerFactory = function () { return new NullLogger(); };
    }

    public function setLoggerFactory(callable $loggerFactory)
    {
        $this->loggerFactory = $loggerFactory;
    }

    public function setProtoRoute(Route $protoRoute)
    {
        $this->protoRoute = $protoRoute;
    }

    public function setRuleFactories(array $ruleFactories)
    {
        $this->ruleFactories = $ruleFactories;
    }

    public function getRuleFactories()
    {
        if (! $this->ruleFactories) {
            $this->ruleFactories = [
                function () { return new \Aura\Router\Rule\Secure(); },
                function () { return new \Aura\Router\Rule\Host(); },
                function () { return new \Aura\Router\Rule\Path(); },
                function () { return new \Aura\Router\Rule\Allows(); },
                function () { return new \Aura\Router\Rule\Accepts(); },
            ];
        }
        return $this->ruleFactories;
    }

    public function getMap()
    {
        if (! $this->map) {
            $this->map = new Map($this->getProtoRoute());
        }
        return $this->map;
    }

    public function getMatcher()
    {
        if (! $this->matcher) {
            $this->matcher = new Matcher(
                $this->getMap(),
                $this->getLogger(),
                $this->getRuleIterator()
            );
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

    public function getRuleIterator()
    {
        if (! $this->ruleIterator) {
            $this->ruleIterator = new RuleIterator($this->getRuleFactories());
        }
        return $this->ruleIterator;
    }

    public function getProtoRoute()
    {
        if (! $this->protoRoute) {
            $this->protoRoute = new Route();
        }
        return $this->protoRoute;
    }
}
