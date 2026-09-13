<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component;

use Runalyze\Bundle\CoreBundle\Component\VariablesContainerTrait;

class VariablesContainerTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var object
     */
    protected $Object;

    protected \ReflectionMethod $GetMethod;

    protected \ReflectionMethod $SetMethod;

    protected function setUp(): void
    {
        $this->Object = $this->getObjectForTrait(VariablesContainerTrait::class);
        $this->GetMethod = new \ReflectionMethod($this->Object, 'get');
        $this->SetMethod = new \ReflectionMethod($this->Object, 'set');
    }

    public function testGettingUnknownKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->GetMethod->invoke($this->Object, 'foobar');
    }

    public function testStoringValue(): void
    {
        $this->SetMethod->invoke($this->Object, 'foo', 'bar');

        $this->assertEquals('bar', $this->GetMethod->invoke($this->Object, 'foo'));
    }

    public function testOverwritingValue(): void
    {
        $this->SetMethod->invoke($this->Object, 'foo', 'bar');
        $this->SetMethod->invoke($this->Object, 'foo', 'baz');

        $this->assertEquals('baz', $this->GetMethod->invoke($this->Object, 'foo'));
    }
}
