<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Weight\Unit;

use Runalyze\Metrics\Weight\Unit\WeightEnum;

final class WeightEnumTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllUnitsCanBeConstructed(): void
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
