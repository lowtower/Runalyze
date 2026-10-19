<?php

declare(strict_types=1);

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

final class TricubeTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeValuesForWidth2(): void
    {
        $Kernel = new Tricube(2);

        $this->assertEquals([
            0.0,
            0.669921875,
            1.0,
            0.193225860595703125,
        ], $Kernel->valuesAt([
            -1.0,
            -0.5,
            0.0,
            0.75,
        ]));
    }

    public function testSomeValuesForWidth10(): void
    {
        $Kernel = new Tricube(10);

        $this->assertEqualsWithDelta([
            0.0,
            0.669921875,
            1.0,
            0.481890304,
        ], $Kernel->valuesAt([
            -5.0,
            -2.5,
            0.0,
            3.0,
        ]), 0.0001);
    }
}
