<?php

namespace Runalyze\Activity\PaceUnit;

#[\PHPUnit\Framework\Attributes\Group('default')]
class MinPerKilometerTest extends \PHPUnit\Framework\TestCase
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
        $Pace = new MinPerKilometer();

        $this->assertEquals(360, $Pace->rawValue(360));
        $this->assertEquals(300, $Pace->rawValue(300));
    }

    public function testComparison(): void
    {
        $Pace = new MinPerKilometer();

        $this->assertEquals(+60, $Pace->rawValue($Pace->compare(300, 360)));
        $this->assertEquals(-60, $Pace->rawValue($Pace->compare(360, 300)));
        $this->assertEquals(0, $Pace->rawValue($Pace->compare(300, 300)));
    }

    public function testFormat(): void
    {
        $Pace = new MinPerKilometer();

        $this->assertEquals('5:00', $Pace->format(300));
        $this->assertEquals('3:44', $Pace->format(224));
        $this->assertEquals('-:--', $Pace->format(0));
    }
}
