<?php

declare(strict_types=1);

namespace Runalyze\Data\Weather;

final class HumidityTest extends \PHPUnit\Framework\TestCase
{
    public function testNonNumericValue(): void
    {
        $this->expectException(\TypeError::class);
        new Humidity('foobar');
    }

    public function testNegativeValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Humidity(-13);
    }

    public function testTooLargeValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Humidity(123);
    }

    public function testNull(): void
    {
        $Humidity = new Humidity();

        $this->assertEquals(null, $Humidity->value());
        $this->assertSame('', $Humidity->string());
        $this->assertTrue($Humidity->isUnknown());
    }

    public function testValue(): void
    {
        $Humidity = new Humidity(73);

        $this->assertEquals(73, $Humidity->value());
        $this->assertEquals(69, $Humidity->set(69)->value());
        $this->assertEquals('69', $Humidity->string(false));
    }
}
