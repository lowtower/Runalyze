<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Distance\Unit;

use Runalyze\Metrics\Distance\Unit\Meter;

final class MeterTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new Meter();

        $this->assertEquals(1000, $unit->fromBaseUnit(1.0));
        $this->assertEqualsWithDelta(1.0, $unit->toBaseUnit(1000), PHP_FLOAT_EPSILON);

        $this->assertEquals(3141, $unit->fromBaseUnit(3.141));
        $this->assertEqualsWithDelta(3.141, $unit->toBaseUnit(3141), PHP_FLOAT_EPSILON);
    }
}
