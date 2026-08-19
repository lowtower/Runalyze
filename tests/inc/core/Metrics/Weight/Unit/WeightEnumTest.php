<?php

namespace Runalyze\Tests\Metrics\Weight\Unit;

use Runalyze\Metrics\Weight\Unit\WeightEnum;

class WeightEnumTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllUnitsCanBeConstructed()
    {
        foreach (WeightEnum::getEnum() as $unit) {
            $weightUnit = WeightEnum::get($unit);
            $this->assertInstanceOf(
                \Runalyze\Metrics\Weight\Unit\AbstractWeightUnit::class,
                $weightUnit,
                "Unit for {$unit} should be an instance of AbstractWeightUnit."
            );
        }
    }
}
