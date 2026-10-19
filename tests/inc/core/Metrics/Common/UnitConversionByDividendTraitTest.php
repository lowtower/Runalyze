<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Common;

use Runalyze\Metrics\Common\UnitConversionByDividendTrait;

final class UnitConversionByDividendTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testThatConversionWorksAsExpected(): void
    {
        $mock = new class() {
            use UnitConversionByDividendTrait;

            public function getDividendFromBaseUnit(): int
            {
                return 5;
            }
        };

        $this->assertEqualsWithDelta(2.5, $mock->fromBaseUnit(2.0), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(2.0, $mock->toBaseUnit(2.5), PHP_FLOAT_EPSILON);
    }
}
