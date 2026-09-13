<?php

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

class AbstractKernelTest_MockTester extends AbstractKernel
{
    protected $DefaultWidth = 1.0;

    protected function atTransformed($difference)
    {
        return $difference;
    }
}

class AbstractKernelTest extends \PHPUnit\Framework\TestCase
{
    public function testZeroKernelWidth(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AbstractKernelTest_MockTester(0);
    }

    public function testNegativeKernelWidth(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AbstractKernelTest_MockTester(-1.23);
    }

    public function testNonNumericKernelWidth(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AbstractKernelTest_MockTester('abc');
    }

    public function testConstructor(): void
    {
        $Object = new AbstractKernelTest_MockTester(1);

        $this->assertEquals(42, $Object->at(42));
    }

    public function testValuesAt(): void
    {
        $Object = new AbstractKernelTest_MockTester(1);

        $this->assertEquals([1, 2, 3, 3.14, 42, 1337], $Object->valuesAt([1, 2, 3, 3.14, 42, 1337]));
    }
}
