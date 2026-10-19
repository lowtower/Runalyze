<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Velocity\Unit;

use Runalyze\Metrics\Velocity\Unit\SecondsPer100y;

final class SecondsPer100yTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new SecondsPer100y();

        $this->assertEqualsWithDelta(27.4, $unit->fromBaseUnit(300), 0.5);
        $this->assertEqualsWithDelta(300, $unit->toBaseUnit(27.4), 0.5);
    }
}
