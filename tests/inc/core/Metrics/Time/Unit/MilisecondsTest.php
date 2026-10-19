<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Time\Unit;

use Runalyze\Metrics\Time\Unit\Miliseconds;

final class MilisecondsTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new Miliseconds();

        $this->assertEqualsWithDelta(234.0, $unit->fromBaseUnit(0.234), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.75, $unit->toBaseUnit(750), PHP_FLOAT_EPSILON);
    }
}
