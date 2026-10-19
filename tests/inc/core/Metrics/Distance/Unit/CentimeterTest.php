<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Distance\Unit;

use Runalyze\Metrics\Distance\Unit\Centimeter;

final class CentimeterTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new Centimeter();

        $this->assertEquals(100, $unit->fromBaseUnit(0.001));
        $this->assertEqualsWithDelta(0.001, $unit->toBaseUnit(100), PHP_FLOAT_EPSILON);
    }
}
