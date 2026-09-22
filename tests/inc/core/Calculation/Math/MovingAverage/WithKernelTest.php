<?php

declare(strict_types=1);

namespace Runalyze\Calculation\Math\MovingAverage;

class WithKernelTest extends \PHPUnit\Framework\TestCase
{
    public function testWithoutKernel(): void
    {
        $this->expectException(\Error::class);
        $Object = new WithKernel([1, 2, 3]);
        $Object->calculate();
    }

    public function testWithUniformKernelWithoutIndexData(): void
    {
        $Object = new WithKernel([10, 20, 30, 20, 30, 50, 40, 50]);
        $Object->setKernel(new Kernel\Uniform(5));
        $Object->calculate();

        $this->assertEqualsWithDelta([
            16,
            18,
            22,
            30,
            34,
            38,
            44,
            48,
        ], $Object->movingAverage(), 0.0001);
    }

    public function testWithUniformKernelWithIndexData(): void
    {
        $Object = new WithKernel(
            [10, 20, 30, 20, 30, 50, 40, 50],
            [0.1, 0.3, 0.4, 0.5, 0.6, 1.0, 1.5, 1.6]
        );
        $Object->setKernel(new Kernel\Uniform(0.4));
        $Object->calculate();

        $this->assertEqualsWithDelta([
            16.66666666666666,
            20.0,
            24.0,
            24.0,
            26.66666666666666,
            50.0,
            41.66666666666666,
            41.66666666666666,
        ], $Object->movingAverage(), 0.0001);
    }
}
