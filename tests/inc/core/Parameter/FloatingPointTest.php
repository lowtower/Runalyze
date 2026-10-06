<?php

declare(strict_types=1);

namespace Runalyze\Parameter;

class FloatingPointTest extends \PHPUnit\Framework\TestCase
{
    public function testSetFromString(): void
    {
        $object = new FloatingPoint(3.14);

        $this->assertEquals(3.14, $object->value());
        $this->assertEquals('3.14', $object->valueAsString());

        $object->set(5);
        $this->assertEquals(5, $object->value());
        $this->assertEquals('5', $object->valueAsString());

        $object->setFromString('17');
        $this->assertEquals(17, $object->value());
    }

    public function testMinMaxOptions(): void
    {
        $object = new FloatingPoint(1.23, [
            'min' => 1.0,
            'max' => 2.0,
        ]);
        $this->assertEquals(1.23, $object->value());

        $object->set(0.12);
        $this->assertEquals(1.0, $object->value());

        $object->set(2.34);
        $this->assertEquals(2.0, $object->value());
    }

    public function testEmptyStringForNotNull(): void
    {
        $object = new FloatingPoint(3.0, [
            'null' => false,
        ]);
        $object->setFromString('');

        $this->assertNotNull($object->value());
        $this->assertFalse($object->isEmpty());
        $this->assertNotSame('', $object->valueAsString());
    }

    public function testEmptyStringForNull(): void
    {
        $object = new FloatingPoint(3.0, [
            'null' => true,
        ]);
        $object->setFromString('');

        $this->assertNull($object->value());
        $this->assertTrue($object->isEmpty());
        $this->assertSame('', $object->valueAsString());
    }

    /**
     * Inconsistency between setFromString('') and set(null): an empty string
     * with 'null' disabled falls back to 0.0 (see testEmptyStringForNotNull),
     * but calling set(null) directly with 'null' disabled and a 'min' bound
     * configured silently clamps to the minimum instead. This happens because
     * PHP's comparison rules treat null as false when compared with < or >,
     * so `null < $min` evaluates to true for any positive $min - the min-clamp
     * branch fires even though $value is null, not a number below the limit.
     */
    public function testSetWithNullWhenNullIsDisabledClampsToMinimumInsteadOfRejecting(): void
    {
        $object = new FloatingPoint(3.0, [
            'min' => 1.0,
            'null' => false,
        ]);

        $object->set(null);

        self::assertSame(1.0, $object->value());
        self::assertFalse($object->isEmpty());
    }
}
