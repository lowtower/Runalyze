<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\GroundContactBalance\Unit;

use Runalyze\Metrics\GroundContactBalance\Unit\PercentLeft;

final class PercentLeftTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new PercentLeft();

        $this->assertEqualsWithDelta(49.5, $unit->fromBaseUnit(4950), PHP_FLOAT_EPSILON);
        $this->assertEquals(5120, $unit->toBaseUnit(51.2));
    }
}
