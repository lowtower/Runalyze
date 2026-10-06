<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Common\Unit;

use Runalyze\Metrics\Common\Unit\Factorial;

class FactorialTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new Factorial('foo', 10);

        $this->assertSame(7.9, $unit->fromBaseUnit(0.79));
        $this->assertSame(4.2, $unit->toBaseUnit(42));
        $this->assertSame('foo', $unit->getAppendix());
    }

    public function testDecimals(): void
    {
        $this->assertSame(2, (new Factorial('foo', 0.01))->getDecimals());
        $this->assertSame(1, (new Factorial('foo', 0.2))->getDecimals());
        $this->assertSame(0, (new Factorial('foo', 10))->getDecimals());
        $this->assertSame(0, (new Factorial('foo', 10, 0))->getDecimals());
        $this->assertSame(2, (new Factorial('foo', 10, 2))->getDecimals());
    }
}
