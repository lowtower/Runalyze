<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Common;

use Runalyze\Metrics\Common\UnitConversionByDividendTrait;

abstract class UnitConversionByDividendTraitTestSubject
{
    use UnitConversionByDividendTrait;
}

class UnitConversionByDividendTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testThatConversionWorksAsExpected()
    {
        /** @var UnitConversionByDividendTraitTestSubject&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock = $this->getMockForTrait(UnitConversionByDividendTrait::class);
        $mock->expects($this->any())
            ->method('getDividendFromBaseUnit')
            ->will($this->returnValue(5));

        $this->assertEquals(2.5, $mock->fromBaseUnit(2.0));
        $this->assertEquals(2.0, $mock->toBaseUnit(2.5));
    }
}
