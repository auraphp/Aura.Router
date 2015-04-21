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
    protected $rules = [];
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

    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }

    public function getRules()
    {
        if (! $this->rules) {
            $this->rules = [
                new \Aura\Router\Rule\Secure(),
                new \Aura\Router\Rule\Host(),
                new \Aura\Router\Rule\Path(),
                new \Aura\Router\Rule\Allows(),
                new \Aura\Router\Rule\Accepts(),
            ];
        }
        return $this->rules;
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
            $this->ruleIterator = new RuleIterator($this->getRules());
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
