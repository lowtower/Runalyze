<?php

declare(strict_types=1);

namespace Runalyze\Data\Weather;

class BeaufortScaleTest extends \PHPUnit\Framework\TestCase
{
    public function testString(): void
    {
        $Beaufort = new BeaufortScale(new WindSpeed(15));
        $this->assertSame('3 bft', $Beaufort->shortString());
        $this->assertSame(3, $Beaufort->value());
        $this->assertEquals('3', $Beaufort->string(false));
        $this->assertSame('3 bft', $Beaufort->string(true));
        $this->assertSame('bft', $Beaufort->unit());
    }

    public function testStaticMethods(): void
    {
        $this->assertSame('3 bft', BeaufortScale::getShortString(new WindSpeed(15)));
    }

    public function testSetter(): void
    {
        $object = new BeaufortScale();

        $this->assertSame(5, $object->set(5)->value());
        $this->assertSame(9, $object->setFromWindSpeed(new WindSpeed(80))->value());
    }

    public function testInvalidValue(): void
    {
        $this->assertFalse((new BeaufortScale())->set(42)->isValid());
    }

    public function testLimits(): void
    {
        $object = new BeaufortScale();
        $wind = new WindSpeed();

        $this->assertSame(0, $object->setFromWindSpeed($wind->set(0))->value());
        $this->assertSame(0, $object->setFromWindSpeed($wind->set(0.99))->value());
        $this->assertSame(1, $object->setFromWindSpeed($wind->set(1))->value());
        $this->assertSame(1, $object->setFromWindSpeed($wind->set(5))->value());
        $this->assertSame(2, $object->setFromWindSpeed($wind->set(6))->value());
        $this->assertSame(2, $object->setFromWindSpeed($wind->set(11))->value());
        $this->assertSame(3, $object->setFromWindSpeed($wind->set(12))->value());
        $this->assertSame(3, $object->setFromWindSpeed($wind->set(19))->value());
        $this->assertSame(4, $object->setFromWindSpeed($wind->set(20))->value());
        $this->assertSame(4, $object->setFromWindSpeed($wind->set(28))->value());
        $this->assertSame(5, $object->setFromWindSpeed($wind->set(29))->value());
        $this->assertSame(5, $object->setFromWindSpeed($wind->set(38))->value());
        $this->assertSame(6, $object->setFromWindSpeed($wind->set(39))->value());
        $this->assertSame(6, $object->setFromWindSpeed($wind->set(49))->value());
        $this->assertSame(7, $object->setFromWindSpeed($wind->set(50))->value());
        $this->assertSame(7, $object->setFromWindSpeed($wind->set(61))->value());
        $this->assertSame(8, $object->setFromWindSpeed($wind->set(62))->value());
        $this->assertSame(8, $object->setFromWindSpeed($wind->set(74))->value());
        $this->assertSame(9, $object->setFromWindSpeed($wind->set(75))->value());
        $this->assertSame(9, $object->setFromWindSpeed($wind->set(88))->value());
        $this->assertSame(10, $object->setFromWindSpeed($wind->set(89))->value());
        $this->assertSame(10, $object->setFromWindSpeed($wind->set(102))->value());
        $this->assertSame(11, $object->setFromWindSpeed($wind->set(103))->value());
        $this->assertSame(11, $object->setFromWindSpeed($wind->set(117))->value());
        $this->assertSame(12, $object->setFromWindSpeed($wind->set(118))->value());
        $this->assertSame(12, $object->setFromWindSpeed($wind->set(1337))->value());
    }
}
