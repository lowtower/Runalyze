<?php

namespace Runalyze\Tests\Mathematics\Distribution;

use Runalyze\Mathematics\Distribution\AbstractDistribution;

class AbstractDistributionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Creates a mock object of AbstractDistribution with a predefined histogram.
     *
     * @param array<int, int> $histogram
     * @return AbstractDistribution&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockWithHistogram(array $histogram): \PHPUnit\Framework\MockObject\MockObject
    {
        /** @var AbstractDistribution&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock = $this->getMockForAbstractClass(AbstractDistribution::class);
        $mock->expects($this->any())
            ->method('histogram')
            ->willReturn($histogram);

        return $mock;
    }

    public function testSomeHistogram(): void
    {
        /** @var AbstractDistribution&\PHPUnit\Framework\MockObject\MockObject $dist */
        $dist = $this->getMockWithHistogram([
            10 => 1,
            15 => 2,
            20 => 1,
        ]);
        $dist->calculateStatistic();

        $this->assertEquals(10, $dist->min());
        $this->assertEquals(15, $dist->mean());
        $this->assertEquals(15, $dist->median());
        $this->assertEquals(15, $dist->mode());
        $this->assertEquals(20, $dist->max());
        $this->assertEquals(12.5, $dist->variance());
        $this->assertEqualsWithDelta(3.5, $dist->stdDev(), 0.1);
        $this->assertEqualsWithDelta(3.5 / 15, $dist->coefficientOfVariation(), 0.1);
    }

    public function testAnotherHistogram(): void
    {
        /** @var AbstractDistribution&\PHPUnit\Framework\MockObject\MockObject $dist */
        $dist = $this->getMockWithHistogram([
            0 => 5,
            80 => 10,
            85 => 10,
            90 => 15,
        ]);
        $dist->calculateStatistic();

        $this->assertEquals(0, $dist->min());
        $this->assertEquals(75, $dist->mean());
        $this->assertEquals(85, $dist->median());
        $this->assertEquals(90, $dist->mode());
        $this->assertEquals(90, $dist->max());
        $this->assertEquals(818.75, $dist->variance());
        $this->assertEqualsWithDelta(28.6, $dist->stdDev(), 0.1);
        $this->assertEqualsWithDelta(28.6 / 75, $dist->coefficientOfVariation(), 0.1);
    }

    public function testEmptyHistogram(): void
    {
        /** @var AbstractDistribution&\PHPUnit\Framework\MockObject\MockObject $dist */
        $dist = $this->getMockWithHistogram([]);
        $dist->calculateStatistic();

        $this->assertEquals(0, $dist->min());
        $this->assertEquals(0, $dist->mean());
        $this->assertEquals(0, $dist->median());
        $this->assertEquals(0, $dist->mode());
        $this->assertEquals(0, $dist->max());
        $this->assertEquals(0, $dist->variance());
        $this->assertEquals(0, $dist->stdDev());
        $this->assertFalse($dist->coefficientOfVariation());
    }

    public function testRequestingNotGeneratedQuantile(): void
    {
        /** @var AbstractDistribution&\PHPUnit\Framework\MockObject\MockObject $dist */
        $dist = $this->getMockWithHistogram([]);

        $this->expectException(\InvalidArgumentException::class);

        $dist->quantile(0.1);
    }

    public function testQuantiles(): void
    {
        /** @var AbstractDistribution&\PHPUnit\Framework\MockObject\MockObject $dist */
        $dist = $this->getMockWithHistogram([
            10 => 1,
            20 => 5,
            30 => 2,
            40 => 2,
        ]);
        $dist->calculateStatistic([0.1, 0.2, 0.5, 0.75, 0.8, 0.9]);

        $this->assertEquals(10, $dist->quantile(0.1));
        $this->assertEquals(20, $dist->quantile(0.2));
        $this->assertEquals(20, $dist->quantile(0.5));
        $this->assertEquals(30, $dist->quantile(0.75));
        $this->assertEquals(30, $dist->quantile(0.8));
        $this->assertEquals(40, $dist->quantile(0.9));
    }
}
