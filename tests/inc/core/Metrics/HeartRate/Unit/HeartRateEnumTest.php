<?php

namespace Runalyze\Tests\Metrics\HeartRate\Unit;

use Runalyze\Metrics\HeartRate\Unit\HeartRateEnum;

class HeartRateEnumTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllUnitsCanBeConstructed(): void
    {
        foreach (HeartRateEnum::getEnum() as $unit) {
            $heartRateUnit = HeartRateEnum::get($unit, 200, 60);
            $this->assertInstanceOf(\Runalyze\Metrics\HeartRate\Unit\AbstractHeartRateUnit::class, $heartRateUnit, "Unit for {$unit} should be an instance of AbstractHeartRateUnit.");
        }
    }

    public function testThatUnknownUnitCantBeConstructed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        HeartRateEnum::get(42, 200, 60);
    }
}
