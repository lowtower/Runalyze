<?php

namespace Runalyze\Tests\Metrics\Temperature\Unit;

use Runalyze\Metrics\Temperature\Unit\TemperatureEnum;

class TemperatureEnumTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllUnitsCanBeConstructed()
    {
        foreach (TemperatureEnum::getEnum() as $unit) {
            $temperatureUnit = TemperatureEnum::get($unit);
            $this->assertInstanceOf(\Runalyze\Metrics\Temperature\Unit\AbstractTemperatureUnit::class, $temperatureUnit, "Unit for {$unit} should be an instance of AbstractTemperatureUnit.");
        }
    }
}
