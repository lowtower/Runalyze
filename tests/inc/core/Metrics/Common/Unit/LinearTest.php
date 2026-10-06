<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Common\Unit;

use Runalyze\Metrics\Common\Unit\Linear;

class LinearTest extends \PHPUnit\Framework\TestCase
{
    public function testSimpleFunction(): void
    {
        $unit = new Linear(
            fn ($value): float => 2.0 * $value + 5.0,
            fn ($value): float => ($value - 5.0) / 2.0,
            'foo',
            1
        );

        $this->assertEquals(7.0, $unit->fromBaseUnit(1.0));
        $this->assertEquals(1.0, $unit->toBaseUnit(7.0));
        $this->assertSame('foo', $unit->getAppendix());
    }
}
