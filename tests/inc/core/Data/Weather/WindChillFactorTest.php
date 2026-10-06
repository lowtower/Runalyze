<?php

declare(strict_types=1);

namespace Runalyze\Data\Weather;

use Runalyze\Activity\Pace;
use Runalyze\Activity\Temperature as ActivityTemperature;
use Runalyze\Parameter\Application\TemperatureUnit;

class WindChillFactorTest extends \PHPUnit\Framework\TestCase
{
    public function testUnknownInputData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new WindChillFactor(
            new WindSpeed(20),
            new ActivityTemperature()
        );
    }

    public function testSimpleExample(): void
    {
        $WindChill = new WindChillFactor(
            new WindSpeed(20),
            new ActivityTemperature(10),
            new Pace(5000, 5)
        );

        $this->assertEqualsWithDelta(7.1, $WindChill->value(), 0.1);
    }

    public function testThatObjectIsCloned(): void
    {
        $Temperature = new ActivityTemperature(37.16, new TemperatureUnit(TemperatureUnit::FAHRENHEIT));
        $WindChill = new WindChillFactor(
            new WindSpeed(0),
            $Temperature
        );

        $this->assertEquals($Temperature->valueInPreferredUnit(), $WindChill->adjustedTemperature()->valueInPreferredUnit());
        $this->assertSame($Temperature->string(true, 2), $WindChill->string(true, 2));
    }
}
