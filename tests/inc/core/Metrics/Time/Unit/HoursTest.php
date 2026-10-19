<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Time\Unit;

use Runalyze\Metrics\Time\Unit\Hours;

final class HoursTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new Hours();

        $this->assertEqualsWithDelta(1.0, $unit->fromBaseUnit(3600), PHP_FLOAT_EPSILON);
        $this->assertEquals(2700, $unit->toBaseUnit(0.75));
    }
}
