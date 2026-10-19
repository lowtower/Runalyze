<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Energy\Unit;

use Runalyze\Metrics\Energy\Unit\EnergyEnum;

final class EnergyEnumTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllUnitsCanBeConstructed(): void
    {
        foreach (EnergyEnum::getEnum() as $unit) {
            $energyUnit = EnergyEnum::get($unit);
            $this->assertInstanceOf(\Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit::class, $energyUnit, "Unit for {$unit} should be an instance of AbstractEnergyUnit.");
        }
    }
}
