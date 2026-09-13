<?php

namespace Runalyze\Tests\Metrics\Velocity\Unit;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

class PaceEnumTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllUnitsCanBeConstructed(): void
    {
        foreach (PaceEnum::getEnum() as $unit) {
            $paceUnit = PaceEnum::get($unit);
            $this->assertInstanceOf(\Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit::class, $paceUnit, "Unit for {$unit} should be an instance of AbstractPaceUnit.");
        }
    }
}
