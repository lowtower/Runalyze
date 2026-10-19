<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Velocity\Unit;

use Runalyze\Metrics\Velocity\Unit\KilometerPerHour;

final class KilometerPerHourTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new KilometerPerHour();

        $this->assertEquals(12, $unit->fromBaseUnit(300));
        $this->assertEquals(300, $unit->toBaseUnit(12));

        $this->assertEquals(10, $unit->fromBaseUnit(360));
        $this->assertEquals(360, $unit->toBaseUnit(10));
    }
}
