<?php

namespace Runalyze\Activity\PaceUnit;

#[\PHPUnit\Framework\Attributes\Group('default')]
class MilesPerHourTest extends \PHPUnit\Framework\TestCase
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
        $Pace = new MilesPerHour();

        $this->assertEqualsWithDelta(6.21, $Pace->rawValue(360), 0.01);
        $this->assertEqualsWithDelta(7.46, $Pace->rawValue(300), 0.01);
    }

    public function testComparison(): void
    {
        $Pace = new MilesPerHour();

        $this->assertEqualsWithDelta(+1.25, $Pace->rawValue($Pace->compare(300, 360)), 0.01);
        $this->assertEqualsWithDelta(-1.25, $Pace->rawValue($Pace->compare(360, 300)), 0.01);
        $this->assertEqualsWithDelta(0.00, $Pace->rawValue($Pace->compare(300, 300)), 0.01);
    }

    public function testFormat(): void
    {
        AbstractDecimalUnit::$DecimalSeparator = ',';
        AbstractDecimalUnit::$Decimals = 1;

        $Pace = new MilesPerHour();

        $this->assertEquals('7,5', $Pace->format(300));
        $this->assertEquals('10,0', $Pace->format(224));
    }
}
