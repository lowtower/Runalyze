<?php

namespace Runalyze\Activity\PaceUnit;

#[\PHPUnit\Framework\Attributes\Group('default')]
class MinPer500mTest extends \PHPUnit\Framework\TestCase
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
        $Pace = new MinPer500m();

        $this->assertEquals(180, $Pace->rawValue(360));
        $this->assertEquals(150, $Pace->rawValue(300));
    }

    public function testComparison(): void
    {
        $Pace = new MinPer500m();

        $this->assertEquals(+30, $Pace->rawValue($Pace->compare(300, 360)));
        $this->assertEquals(-30, $Pace->rawValue($Pace->compare(360, 300)));
        $this->assertEquals(0, $Pace->rawValue($Pace->compare(300, 300)));
    }

    public function testFormat(): void
    {
        $Pace = new MinPer500m();

        $this->assertSame('2:30', $Pace->format(300));
        $this->assertSame('1:52', $Pace->format(224));
        $this->assertSame('-:--', $Pace->format(0));
    }
}
