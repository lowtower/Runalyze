<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Activity\Tool;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Activity\Tool\BestSubSegmentsStatistics;
use Runalyze\Calculation\Math\SubSegmentMaximization;
use Runalyze\Model\Trackdata\Entity as TrackdataEntity;

final class BestSubSegmentsStatisticsTest extends TestCase
{
    /**
     * @param int[] $time
     * @param float[] $distance
     */
    private function createTrackdataMock(array $time, array $distance): TrackdataEntity
    {
        $mock = $this->createStub(TrackdataEntity::class);
        $mock->method('has')
            ->willReturnCallback(
                static fn (string $key): bool => in_array($key, [TrackdataEntity::TIME, TrackdataEntity::DISTANCE], true)
            );
        $mock->method('num')
            ->willReturn(count($time));
        $mock->method('time')
            ->willReturn($time);
        $mock->method('distance')
            ->willReturn($distance);

        return $mock;
    }

    public function testConstructorThrowsWhenTimeIsMissing(): void
    {
        $trackdata = $this->createStub(TrackdataEntity::class);
        $trackdata->method('has')
            ->willReturnCallback(
                static fn (string $key): bool => $key === TrackdataEntity::DISTANCE
            );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Provided trackdata object must have time and distance array.');

        new BestSubSegmentsStatistics($trackdata);
    }

    public function testConstructorThrowsWhenDistanceIsMissing(): void
    {
        $trackdata = $this->createStub(TrackdataEntity::class);
        $trackdata->method('has')
            ->willReturnCallback(
                static fn (string $key): bool => $key === TrackdataEntity::TIME
            );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Provided trackdata object must have time and distance array.');

        new BestSubSegmentsStatistics($trackdata);
    }

    public function testConstructorSucceedsWhenBothTimeAndDistanceArePresent(): void
    {
        $trackdata = $this->createTrackdataMock([0, 1, 2], [0.0, 1.0, 2.0]);

        $stats = new BestSubSegmentsStatistics($trackdata);

        $this->assertInstanceOf(BestSubSegmentsStatistics::class, $stats);
    }

    public function testSetAndGetDistancesToAnalyze(): void
    {
        $stats = new BestSubSegmentsStatistics($this->createTrackdataMock([0, 1], [0.0, 1.0]));

        $stats->setDistancesToAnalyze([1000, 5000]);

        $this->assertSame([1000, 5000], $stats->getDistances());
    }

    public function testSetAndGetTimesToAnalyze(): void
    {
        $stats = new BestSubSegmentsStatistics($this->createTrackdataMock([0, 1], [0.0, 1.0]));

        $stats->setTimesToAnalyze([300, 600]);

        $this->assertSame([300, 600], $stats->getTimes());
    }

    public function testGetDistancesDefaultsToEmptyArray(): void
    {
        $stats = new BestSubSegmentsStatistics($this->createTrackdataMock([0, 1], [0.0, 1.0]));

        $this->assertSame([], $stats->getDistances());
    }

    public function testGetTimesDefaultsToEmptyArray(): void
    {
        $stats = new BestSubSegmentsStatistics($this->createTrackdataMock([0, 1], [0.0, 1.0]));

        $this->assertSame([], $stats->getTimes());
    }

    public function testGetDistanceSegmentsReturnsNullBeforeFindSegmentsIsCalled(): void
    {
        $stats = new BestSubSegmentsStatistics($this->createTrackdataMock([0, 1], [0.0, 1.0]));

        $this->assertNull($stats->getDistanceSegments());
    }

    public function testGetTimeSegmentsReturnsNullBeforeFindSegmentsIsCalled(): void
    {
        $stats = new BestSubSegmentsStatistics($this->createTrackdataMock([0, 1], [0.0, 1.0]));

        $this->assertNull($stats->getTimeSegments());
    }

    public function testFindSegmentsComputesDeltasAndMinimizesTimeForDistanceSegments(): void
    {
        $trackdata = $this->createTrackdataMock(
            time: [0, 10, 20, 30],
            distance: [0, 100, 300, 350]
        );

        $stats = new BestSubSegmentsStatistics($trackdata);
        $stats->setDistancesToAnalyze([100]);
        $stats->findDistanceSegments();

        $distanceSegments = $stats->getDistanceSegments();
        $this->assertInstanceOf(SubSegmentMaximization::class, $distanceSegments);
        $this->assertTrue($distanceSegments->hasMaximumForLengthIndex(0));
        $this->assertEqualsWithDelta(5.0, $distanceSegments->getMaximumForLengthIndex(0), PHP_FLOAT_EPSILON);
    }

    public function testFindSegmentsComputesDeltasAndMaximizesDistanceForTimeSegments(): void
    {
        $trackdata = $this->createTrackdataMock(
            time: [0, 10, 20, 30],
            distance: [0, 100, 300, 350]
        );

        $stats = new BestSubSegmentsStatistics($trackdata);
        $stats->setTimesToAnalyze([10]);
        $stats->findTimeSegments();

        $timeSegments = $stats->getTimeSegments();
        $this->assertInstanceOf(SubSegmentMaximization::class, $timeSegments);
        $this->assertTrue($timeSegments->hasMaximumForLengthIndex(0));
        $this->assertEqualsWithDelta(200.0, $timeSegments->getMaximumForLengthIndex(0), PHP_FLOAT_EPSILON);
    }

    public function testFindSegmentsLeavesFirstDeltaAsRawStartingValue(): void
    {
        $trackdata = $this->createTrackdataMock(
            time: [100, 110],
            distance: [9999.0, 10009.0]
        );

        $stats = new BestSubSegmentsStatistics($trackdata);
        $stats->setTimesToAnalyze([10]);
        $stats->findTimeSegments();

        $timeSegments = $stats->getTimeSegments();
        $this->assertInstanceOf(\Runalyze\Calculation\Math\SubSegmentMaximization::class, $timeSegments);
        $this->assertEqualsWithDelta(999.9, $timeSegments->getMaximumForLengthIndex(0), 0.0001);
    }
}
