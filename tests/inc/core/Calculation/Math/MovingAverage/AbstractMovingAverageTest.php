<?php

declare(strict_types=1);

namespace Runalyze\Calculation\Math\MovingAverage;

class AbstractMovingAverage_MockTester extends AbstractMovingAverage
{
    public function calculateWithIndexData(): void
    {
        $this->MovingAverage = $this->IndexData;
    }

    public function calculateWithoutIndexData(): void
    {
        $this->MovingAverage = $this->Data;
    }
}

final class AbstractMovingAverageTest extends \PHPUnit\Framework\TestCase
{
    public function testWrongConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AbstractMovingAverage_MockTester([1, 2, 3], [1, 2]);
    }

    public function testInvalidAt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $Object = new AbstractMovingAverage_MockTester([1, 2, 3]);
        $Object->at(1);
    }

    public function testWithIndexData(): void
    {
        $Object = new AbstractMovingAverage_MockTester([1, 2, 3], [10, 20, 30]);
        $Object->calculate();

        $this->assertEquals([10, 20, 30], $Object->movingAverage());
    }

    public function testWithNonAscendingIndexData(): void
    {
        $Object = new AbstractMovingAverage_MockTester([1, 2, 3], [10, 10, 10], false);
        $Object->calculate();

        $this->assertEquals([10, 20, 30], $Object->movingAverage());
    }

    public function testWithoutIndexData(): void
    {
        $Object = new AbstractMovingAverage_MockTester([1, 2, 3]);
        $Object->calculate();

        $this->assertEquals([1, 2, 3], $Object->movingAverage());
        $this->assertEquals(1, $Object->at(0));
        $this->assertEquals(2, $Object->at(1));
        $this->assertEquals(3, $Object->at(2));
    }
}
