<?php

declare(strict_types=1);

namespace Runalyze\Data\Weather;

final class PressureTest extends \PHPUnit\Framework\TestCase
{
    public function testNonNumericValue(): void
    {
        $this->expectException(\TypeError::class);
        new Pressure('foobar');
    }

    public function testNull(): void
    {
        $Pressure = new Pressure();

        $this->assertEquals(null, $Pressure->value());
        $this->assertSame('', $Pressure->string());
        $this->assertTrue($Pressure->isUnknown());
    }

    public function testValue(): void
    {
        $Pressure = new Pressure(1090);

        $this->assertEquals(1090, $Pressure->value());
        $this->assertEquals(1063, $Pressure->set(1063)->value());
        $this->assertEquals('1063', $Pressure->string(false));
    }
}
