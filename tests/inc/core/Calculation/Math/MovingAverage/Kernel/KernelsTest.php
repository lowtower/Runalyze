<?php

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

class KernelsTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidKernel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Kernels::get(-1, 5);
    }

    public function testAllConstructors(): void
    {
        $expectedClasses = [
            Kernels::UNIFORM => \Runalyze\Calculation\Math\MovingAverage\Kernel\Uniform::class,
            Kernels::TRIANGULAR => \Runalyze\Calculation\Math\MovingAverage\Kernel\Triangular::class,
            Kernels::EPANECHNIKOV => \Runalyze\Calculation\Math\MovingAverage\Kernel\Epanechnikov::class,
            Kernels::QUARTIC => \Runalyze\Calculation\Math\MovingAverage\Kernel\Quartic::class,
            Kernels::TRIWEIGHT => \Runalyze\Calculation\Math\MovingAverage\Kernel\Triweight::class,
            Kernels::TRICUBE => \Runalyze\Calculation\Math\MovingAverage\Kernel\Tricube::class,
            Kernels::GAUSSIAN => \Runalyze\Calculation\Math\MovingAverage\Kernel\Gaussian::class,
            Kernels::COSINE => \Runalyze\Calculation\Math\MovingAverage\Kernel\Cosine::class,
            Kernels::LOGISTIC => \Runalyze\Calculation\Math\MovingAverage\Kernel\Logistic::class,
        ];

        foreach (Kernels::getEnum() as $kernelid) {
            $kernel = Kernels::get($kernelid, 5.0);
            $expectedClass = $expectedClasses[$kernelid];

            $this->assertInstanceOf(
                $expectedClass,
                $kernel,
                "Kernel for ID {$kernelid} should be an instance of {$expectedClass}"
            );
        }
    }

    public function testNormalizations(): void
    {
        $x = range(-1.0, 1.0, 0.01);
        $num = count($x);

        foreach (Kernels::getEnum() as $kernelid) {
            $Kernel = Kernels::get($kernelid, 2.0);
            $sum = array_sum($Kernel->valuesAt($x, true));

            $this->assertEqualsWithDelta(1.0, $sum / $num, 0.02, 'Kernel with id "' . $kernelid . '" is not normalized, sum is ' . $sum);
        }
    }
}
