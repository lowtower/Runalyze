<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Common;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

abstract class UnitConversionByFactorTraitTestSubject
{
    use UnitConversionByFactorTrait;
}

class UnitConversionByFactorTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testThatConversionWorksAsExpected(): void
    {
        /** @var UnitConversionByFactorTraitTestSubject&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock = $this->getMockForAbstractClass(UnitConversionByFactorTraitTestSubject::class);
        $mock->expects($this->any())
            ->method('getFactorFromBaseUnit')
            ->will($this->returnValue(1.23));

        $this->assertEquals(2.46, $mock->fromBaseUnit(2.0));
        $this->assertEquals(2.0, $mock->toBaseUnit(2.46));
    }
}
