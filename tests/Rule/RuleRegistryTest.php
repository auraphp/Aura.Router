<?php
namespace Aura\Router\Rule;

class RuleRegistryTest extends \PHPUnit_Framework_TestCase
{
    protected $ruleRegistry;

    protected function setUp()
    {
        parent::setUp();
        $this->ruleRegistry = new RuleRegistry();
    }

    public function test()
    {
        $this->ruleRegistry->set([
            new Secure(),
            function () { return new FakeCustom(); },
        ]);
        $this->ruleRegistry->prepend(new Allows());

        $expect = [
            'Aura\Router\Rule\Allows',
            'Aura\Router\Rule\Secure',
            'Aura\Router\Rule\FakeCustom',
        ];

        // first traversal
        $actual = [];
        foreach ($this->ruleRegistry as $key => $rule) {
            $actual[] = get_class($rule);
        }
        $this->assertSame($actual, $expect);

        // subsequent traversal
        $actual = [];
        foreach ($this->ruleRegistry as $key => $rule) {
            $actual[] = get_class($rule);
        }
        $this->assertSame($actual, $expect);
    }

    public function testUnexpectedValue()
    {
        $this->ruleRegistry->set([
            function () { return 'string'; }
        ]);

        $this->setExpectedException(
            'Aura\Router\Exception\UnexpectedValue',
            'string'
        );

        $this->ruleRegistry->current();
    }

    public function testUnexpectedValueObject()
    {
        $this->ruleRegistry->set([
            function () { return (object) []; }
        ]);

        $this->setExpectedException(
            'Aura\Router\Exception\UnexpectedValue',
            'object of type stdClass'
        );

        $this->ruleRegistry->current();
    }
}
