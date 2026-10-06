<?php

namespace Runalyze\Activity\PaceUnit;

use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\Group('default')]
class KmPerHourTest extends TestCase
{
    private string $OriginalDecimalSeparator;

    private int $OriginalDecimals;

    protected function setUp(): void
    {
        $this->OriginalDecimalSeparator = AbstractDecimalUnit::$DecimalSeparator;
        $this->OriginalDecimals = AbstractDecimalUnit::$Decimals;
    }

    protected function tearDown(): void
    {
        AbstractDecimalUnit::$DecimalSeparator = $this->OriginalDecimalSeparator;
        AbstractDecimalUnit::$Decimals = $this->OriginalDecimals;
    }

    public function testSomePaces(): void
    {
        $Pace = new KmPerHour();

        $this->assertEquals(10, $Pace->rawValue(360));
        $this->assertEquals(12, $Pace->rawValue(300));
    }

    public function testComparison(): void
    {
        $Pace = new KmPerHour();

        $this->assertEquals(+2, $Pace->rawValue($Pace->compare(300, 360)));
        $this->assertEquals(-2, $Pace->rawValue($Pace->compare(360, 300)));
        $this->assertEquals(0, $Pace->rawValue($Pace->compare(300, 300)));
    }

    public function testFormat(): void
    {
        AbstractDecimalUnit::$DecimalSeparator = ',';
        AbstractDecimalUnit::$Decimals = 1;

        $Pace = new KmPerHour();

        $this->assertSame('12,0', $Pace->format(300));
        $this->assertSame('16,1', $Pace->format(224));
        $this->assertSame('0,0', $Pace->format(0));
    }
}
