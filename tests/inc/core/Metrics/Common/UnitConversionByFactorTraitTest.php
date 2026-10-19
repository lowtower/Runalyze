<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Common;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

final class UnitConversionByFactorTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testThatConversionWorksAsExpected(): void
    {
        $mock = new class() {
            use UnitConversionByFactorTrait;

            public function getFactorFromBaseUnit(): float
            {
                return 1.23;
            }
        };

        $this->assertEqualsWithDelta(2.46, $mock->fromBaseUnit(2.0), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(2.0, $mock->toBaseUnit(2.46), PHP_FLOAT_EPSILON);
    }
}
