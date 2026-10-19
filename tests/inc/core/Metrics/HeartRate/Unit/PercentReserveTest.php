<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\HeartRate\Unit;

use Runalyze\Metrics\HeartRate\Unit\PercentReserve;

final class PercentReserveTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new PercentReserve(200, 60);

        $this->assertSame(200, $unit->getMaximalHeartRate());
        $this->assertSame(60, $unit->getRestingHeartRate());

        $this->assertEqualsWithDelta(0.29, $unit->fromBaseUnit(100), 0.01);
        $this->assertEqualsWithDelta(100.6, $unit->toBaseUnit(0.29), 0.01);

        $this->assertEqualsWithDelta(0.71, $unit->fromBaseUnit(160), 0.01);
        $this->assertEqualsWithDelta(159.4, $unit->toBaseUnit(0.71), 0.01);
    }

    public function testWithOtherMaximalHeartRate(): void
    {
        $unit = new PercentReserve(175, 75);

        $this->assertSame(175, $unit->getMaximalHeartRate());
        $this->assertSame(75, $unit->getRestingHeartRate());

        $this->assertEqualsWithDelta(0.45, $unit->fromBaseUnit(120), PHP_FLOAT_EPSILON);
        $this->assertEquals(120, $unit->toBaseUnit(0.45));

        $this->assertEqualsWithDelta(0.75, $unit->fromBaseUnit(150), PHP_FLOAT_EPSILON);
        $this->assertEquals(150, $unit->toBaseUnit(0.75));
    }
}
