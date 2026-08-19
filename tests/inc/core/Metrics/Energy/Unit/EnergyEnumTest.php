<?php

namespace Runalyze\Tests\Metrics\Energy\Unit;

use Runalyze\Metrics\Energy\Unit\EnergyEnum;

class EnergyEnumTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllUnitsCanBeConstructed()
    {
        foreach (EnergyEnum::getEnum() as $unit) {
            $energyUnit = EnergyEnum::get($unit);
            $this->assertInstanceOf(\Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit::class, $energyUnit, "Unit for {$unit} should be an instance of AbstractEnergyUnit.");
        }
    }
}
