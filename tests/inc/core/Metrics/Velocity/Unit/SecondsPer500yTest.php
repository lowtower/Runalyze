<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Velocity\Unit;

use Runalyze\Metrics\Velocity\Unit\SecondsPer500y;

final class SecondsPer500yTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new SecondsPer500y();

        $this->assertEqualsWithDelta(137, $unit->fromBaseUnit(300), 0.5);
        $this->assertEqualsWithDelta(300, $unit->toBaseUnit(137), 0.5);
    }
}
