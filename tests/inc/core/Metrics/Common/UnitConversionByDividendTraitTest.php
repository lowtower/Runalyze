<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Common;

use Runalyze\Metrics\Common\UnitConversionByDividendTrait;

class UnitConversionByDividendTraitTest extends \PHPUnit\Framework\TestCase
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

        $this->assertSame(2.5, $mock->fromBaseUnit(2.0));
        $this->assertSame(2.0, $mock->toBaseUnit(2.5));
    }
}
