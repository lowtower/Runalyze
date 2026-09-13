<?php

declare(strict_types=1);

namespace Runalyze\Tests\Mathematics\Numerics;

use Runalyze\Mathematics\Numerics\Bisection;

class BisectionTest extends \PHPUnit\Framework\TestCase
{
    public function testPerfectStartPoint(): void
    {
        $Bisection = new Bisection(5, 0, 10, fn ($x) => $x);

        $this->assertEquals(5, $Bisection->findValue());
    }

    public function testNotEnoughIterations(): void
    {
        $Bisection = new Bisection(1, 0, 10, fn ($x) => $x);

        $Bisection->setIterations(4);
        $Bisection->setEpsilon(0.01);

        $this->assertEquals(0.9375, $Bisection->findValue());
    }

    public function testSimpleApproximation(): void
    {
        $Bisection = new Bisection(1, 0, 10, fn ($x) => $x);

        $Bisection->setIterations(100);
        $Bisection->setEpsilon(0.01);

        $this->assertEqualsWithDelta(1, $Bisection->findValue(), 0.01);
    }

    public function testCubicFunction(): void
    {
        $Bisection = new Bisection(7, -50, 20, fn ($x) => $x * $x * $x + 6);

        $Bisection->setIterations(100);
        $Bisection->setEpsilon(0.01);

        $this->assertEqualsWithDelta(1, $Bisection->findValue(), 0.01);
    }
}
