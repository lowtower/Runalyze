<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Weight\Unit;

use Runalyze\Metrics\Weight\Unit\Pounds;

final class PoundsTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new Pounds();

        $this->assertEqualsWithDelta(165.3, $unit->fromBaseUnit(75), 0.1);
        $this->assertEqualsWithDelta(75, $unit->toBaseUnit(165.3), 0.1);

        $this->assertEqualsWithDelta(10, $unit->fromBaseUnit(4.54), 0.1);
        $this->assertEqualsWithDelta(4.54, $unit->toBaseUnit(10), 0.1);

        $this->assertEqualsWithDelta(22.04, $unit->fromBaseUnit(10), 0.1);
        $this->assertEqualsWithDelta(10, $unit->toBaseUnit(22.04), 0.1);
    }
}
