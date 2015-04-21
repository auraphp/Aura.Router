<?php
namespace Aura\Router\Rule;

use Aura\Router\Exception;
use Iterator;

class RuleRegistry implements Iterator
{
    protected $rules = [];

    public function __construct(array $rules)
    {
        $this->set($rules);
    }

    public function set(array $rules)
    {
        $this->rules = [];
        foreach ($rules as $rule) {
            $this->append($rule);
        }
    }

    public function append(callable $rule)
    {
        $this->rules[] = $rule;
    }

    public function prepend(callable $rule)
    {
        array_unshift($rule, $this->rules);
    }

    public function current()
    {
        $rule = current($this->rules);
        if ($rule instanceof RuleInterface) {
            return $rule;
        }

        // treat it as a factory
        $rule = $rule();
        if (! $rule instanceof RuleInterface) {
            throw new Exception\UnexpectedValue(get_class($rule));
        }

        // retain and return
        $key = key($this->rules);
        $this->rules[$key] = $rule;
        return $rule;
    }

    public function key()
    {
        return key($this->rules);;
    }

    public function next()
    {
        next($this->rules);
    }

    public function rewind()
    {
        reset($this->rules);
    }

    public function valid()
    {
        return current($this->rules) !== false;
    }
}
