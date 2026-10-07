<?php

declare(strict_types=1);

namespace Runalyze\Tests\Mathematics\Distribution;

use Runalyze\Mathematics\Distribution\TimeSeries;

class TimeSeriesTest extends \PHPUnit\Framework\TestCase
{
    public function testSimpleArray(): void
    {
        $dist = new TimeSeries(
            [10, 15, 20, 15],
            [1, 3, 10, 13]
        );

        $this->assertEquals([
            10 => 1,
            15 => 5,
            20 => 7,
        ], $dist->histogram());
    }

    public function testEmptyTimeArrayIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'Time array must not be empty.'
        );

        new TimeSeries(
            [10, 20, 30],
            []
        );
    }

    public function testTimeArrayMustNotBeSmallerThanDataArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'Time array must be at least as large as data array.'
        );

        new TimeSeries(
            [10, 20, 30],
            [1, 2]
        );
    }

    public function testTimeArrayMayHaveSameSizeAsDataArray(): void
    {
        $series = new TimeSeries(
            [10, 20, 30],
            [1, 2, 3]
        );

        $this->assertSame(
            [
                10 => 1,
                20 => 1,
                30 => 1,
            ],
            $series->histogram()
        );
    }

    public function testAdditionalTimeValuesAreIgnored(): void
    {
        $series = new TimeSeries(
            [10, 20],
            [1, 2, 100]
        );

        $this->assertEquals(
            [
                10 => 1,
                20 => 1,
            ],
            $series->histogram()
        );
    }

    public function testDescendingTimeValuesAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'Time values must be ordered ascending.'
        );

        new TimeSeries(
            [10, 20, 30],
            [1, 3, 2]
        );
    }

    public function testEqualConsecutiveTimeValuesAreAcceptedAndProduceZeroDuration(): void
    {
        $series = new TimeSeries(
            [10, 20],
            [5, 5]
        );

        $this->assertSame(
            [
                10 => 5,
                20 => 0,
            ],
            $series->histogram()
        );
    }

    public function testEmptyDataArrayWithNonEmptyTimeArrayResultsInEmptyHistogram(): void
    {
        $series = new TimeSeries(
            [],
            [1, 2]
        );

        $this->assertSame([], $series->histogram());
    }

    public function testAllHistogramValuesAreIntegersEvenWithFloatLikeTimeValues(): void
    {
        $series = new TimeSeries(
            [10, 15, 20, 15],
            [1, 3, 10, 13]
        );

        foreach ($series->histogram() as $heartRate => $duration) {
            $this->assertIsInt($duration, sprintf('Duration for heart rate %d must be an int.', $heartRate));
        }

        $this->assertSame(
            [
                10 => 1,
                15 => 5,
                20 => 7,
            ],
            $series->histogram()
        );
    }
}
