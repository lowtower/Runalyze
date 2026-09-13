<?php

namespace Runalyze\Parameter;

class ParameterSetTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Runalyze\Parameter\Set
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new Set([]);
    }

    public function testArrayToString(): void
    {
        $this->assertEquals([], $this->object->value());
        $this->assertEquals('', $this->object->valueAsString());

        $this->object->set(['a', 'b', 'c']);
        $this->assertEquals(['a', 'b', 'c'], $this->object->value());
        $this->assertEquals('a,b,c', $this->object->valueAsString());

        $this->object->setFromString('1,2,3');
        $this->assertEquals(['1', '2', '3'], $this->object->value());
    }

    public function testAppend(): void
    {
        $this->assertEquals([], $this->object->value());
        $this->assertEquals('', $this->object->valueAsString());

        $this->object->append('one');
        $this->assertEquals(['one'], $this->object->value());

        $this->object->append('two');
        $this->assertEquals(['one', 'two'], $this->object->value());
    }

    public function testMaxLength(): void
    {
        $maxString = str_repeat('a', \Runalyze\Parameter::MAX_LENGTH);

        $this->object->append($maxString);
        $this->object->setFromString($this->object->valueAsString());
        $this->assertEquals([$maxString], $this->object->value());

        $this->object->append('foo');
        $this->object->setFromString($this->object->valueAsString());
        $this->assertEquals(['foo'], $this->object->value());
    }
}
