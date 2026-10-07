<?php

declare(strict_types=1);

namespace Runalyze\Tests\Mathematics\Distribution;

use PHPUnit\Framework\TestCase;
use Runalyze\Mathematics\Distribution\EmpiricalDistribution;
use Runalyze\Mathematics\Distribution\MultipleTimeSeries;

class MultipleTimeSeriesTest extends TestCase
{
    public function testSimpleExample(): void
    {
        $object = new MultipleTimeSeries();

        $object->generateDistributionsFor([
            'foo' => [10, 15, 10, 15, 10],
            'bar' => [1, 2, 3, 2, 1],
        ], [1, 2, 8, 9, 10]);

        $this->assertInstanceOf(
            EmpiricalDistribution::class,
            $object->getDistribution('foo')
        );
        $this->assertInstanceOf(
            EmpiricalDistribution::class,
            $object->getDistribution('bar')
        );
        $this->assertSame(11.0, $object->getDistribution('foo')->mean());
        $this->assertSame(2.4, $object->getDistribution('bar')->mean());
    }

    public function testTimeDifferencesAreUsedAsHistogramWeights(): void
    {
        $object = new MultipleTimeSeries();

        $object->generateDistributionsFor([
            'foo' => [10, 15, 10, 15, 10],
        ], [1, 2, 8, 9, 10]);

        $this->assertEquals(
            [
                10 => 8,
                15 => 2,
            ],
            $object->getDistribution('foo')
                ->histogram()
        );
    }

    public function testZeroValuesAreRemovedByDefault(): void
    {
        $object = new MultipleTimeSeries();

        $object->generateDistributionsFor([
            'foo' => [0, 1, 0],
        ], [1, 2, 5]);

        $this->assertEquals(
            [
                1 => 1.0,
            ],
            $object->getDistribution('foo')
                ->histogram()
        );
    }

    public function testZeroValuesCanBeAllowedForSelectedSeries(): void
    {
        $object = new MultipleTimeSeries();

        $object->generateDistributionsFor([
            'foo' => [0, 1, 0],
            'bar' => [0, 2, 0],
        ], [1, 2, 5], ['foo']);

        $this->assertEquals(
            [
                0 => 4.0,
                1 => 1.0,
            ],
            $object->getDistribution('foo')
                ->histogram()
        );

        $this->assertEquals(
            [
                2 => 1.0,
            ],
            $object->getDistribution('bar')
                ->histogram()
        );
    }

    public function testEmptyTimeArrayIsRejected(): void
    {
        $object = new MultipleTimeSeries();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Time array must not be empty.');

        $object->generateDistributionsFor([
            'foo' => [10, 20, 15],
        ], []);
    }

    public function testAskingForUnknownDistribution(): void
    {
        $object = new MultipleTimeSeries();

        $object->generateDistributionsFor([
            'foo' => [10, 20, 15],
        ], [1, 2, 3]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Unknown distribution key "bar"');

        $object->getDistribution('bar');
    }

    public function testOnlyTimeSeriesGiven(): void
    {
        $object = new MultipleTimeSeries();

        $object->generateDistributionsFor([], [1, 2, 3]);

        $this->expectException(\InvalidArgumentException::class);

        $object->getDistribution('anything');
    }

    public function testDescendingTimeValuesAreRejected(): void
    {
        $object = new MultipleTimeSeries();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Time values must be ordered ascending.');

        $object->generateDistributionsFor([
            'foo' => [10, 20, 30],
        ], [1, 3, 2]);
    }

    public function testEqualTimeValuesAreAllowed(): void
    {
        $object = new MultipleTimeSeries();

        $object->generateDistributionsFor([
            'foo' => [10, 20, 30],
        ], [1, 1, 2]);

        $this->assertInstanceOf(
            EmpiricalDistribution::class,
            $object->getDistribution('foo')
        );
    }

    public function testDescendingValueAtEndOfSeriesIsRejected(): void
    {
        $object = new MultipleTimeSeries();

        $this->expectException(\InvalidArgumentException::class);

        $object->generateDistributionsFor([
            'foo' => [10, 20, 30, 40],
        ], [1, 2, 3, 2]);
    }

    public function testNullValuesAreIgnored(): void
    {
        $object = new MultipleTimeSeries();

        $object->generateDistributionsFor([
            'foo' => [10, null, 20],
        ], [1, 2, 3]);

        $this->assertEquals(
            [
                10 => 1.0,
                20 => 1.0,
            ],
            $object->getDistribution('foo')
                ->histogram()
        );
    }
}
