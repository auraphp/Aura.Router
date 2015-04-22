<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

use Aura\Router\Rule;
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
    protected $map;
    protected $mapFactory;
    protected $matcher;
    protected $ruleIterator;
    protected $rules = [];

    public function __construct()
    {
        $this->setLoggerFactory(function () {
            return new NullLogger();
        });

        $this->setMapFactory(function () {
            return new Map(new Route());
        });

        $this->setMapBuilder(function (Map $map) {
            // do nothing
        });
    }

    public function setLoggerFactory(callable $loggerFactory)
    {
        $this->loggerFactory = $loggerFactory;
    }

    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }

    public function getRules()
    {
        if (! $this->rules) {
            $this->rules = [
                new Rule\Secure(),
                new Rule\Host(),
                new Rule\Path(),
                new Rule\Allows(),
                new Rule\Accepts(),
            ];
        }
        return $this->rules;
    }

    public function setMapFactory(callable $mapFactory)
    {
        $this->mapFactory = $mapFactory;
    }

    public function setMapBuilder(callable $mapBuilder)
    {
        $this->mapBuilder = $mapBuilder;
    }

    public function getMap()
    {
        if (! $this->map) {
            $this->map = call_user_func($this->mapFactory);
            call_user_func($this->mapBuilder, $this->map);
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
            $this->ruleIterator = new Rule\RuleIterator($this->getRules());
        }
        return $this->ruleIterator;
    }
}
