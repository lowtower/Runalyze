<?php

namespace Runalyze\Tests\Metrics\Time\Unit;

use Runalyze\Metrics\Time\Unit\Miliseconds;

class MilisecondsTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new Miliseconds();

        $this->assertSame(234.0, $unit->fromBaseUnit(0.234));
        $this->assertSame(0.75, $unit->toBaseUnit(750));
    }
}
