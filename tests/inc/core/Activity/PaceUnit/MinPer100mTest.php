<?php

namespace Runalyze\Activity\PaceUnit;

class MinPer100mTest extends \PHPUnit\Framework\TestCase
{
    public function testSomePaces(): void
    {
        $Pace = new MinPer100m();

        $this->assertEquals(36, $Pace->rawValue(360));
        $this->assertEquals(30, $Pace->rawValue(300));
    }

    public function testComparison(): void
    {
        $Pace = new MinPer100m();

        $this->assertEquals(+6, $Pace->rawValue($Pace->compare(300, 360)));
        $this->assertEquals(-6, $Pace->rawValue($Pace->compare(360, 300)));
        $this->assertEquals(0, $Pace->rawValue($Pace->compare(300, 300)));
    }

    public function testFormat(): void
    {
        $Pace = new MinPer100m();

        $this->assertEquals('0:30', $Pace->format(300));
        $this->assertEquals('0:22', $Pace->format(224));
        $this->assertEquals('-:--', $Pace->format(0));
    }
}
