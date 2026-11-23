<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapeDailyAggregator;

#[Group('plugin')]
final class RechenspieleShapeDailyAggregatorTest extends TestCase
{
    public function testAggregatesMultipleActivitiesPerDayAndTrimpFromOtherSports(): void
    {
        $result = $this->aggregator()->aggregate([
            $this->activity('2026-11-21', 10.0, 5.0, 125.0, 25.0, true),
            $this->activity('2026-11-21', 20.0, 12.0, 300.0, 50.0, true),
            $this->activity('2026-11-21', 30.0, 90.0, 900.0, 90.0, false),
            $this->activity('2026-11-22', 4.0, 6.0, 0.0, 0.0, true),
        ], $this->day('2026-11-21'), 2, 2, 10.0);

        $this->assertSame([0.0, 0.0, 60.0, 4.0, 0.0, 0.0], $result['trimp']);
        $this->assertSame([0.0, 0.0, 17.0, 6.0, 0.0, 0.0], $result['distance']);
        $this->assertSame([0.0, 0.0, 425.0, 0.0, 0.0, 0.0], $result['vo2max_weighted']);
        $this->assertSame([0.0, 0.0, 75.0, 0.0, 0.0, 0.0], $result['vo2max_sum_time']);
        $this->assertSame([[], [], [12.0], [], [], []], $result['longjogs']);
    }

    public function testMapsWarmupDaysAndExcludesOutOfRangeActivities(): void
    {
        $result = $this->aggregator()->aggregate([
            $this->activity('2026-11-17', 40.0),
            $this->activity('2026-11-18', 10.0),
            $this->activity('2026-11-19', 20.0),
            $this->activity('2026-11-20', 30.0),
            $this->activity('2026-11-23', 50.0),
        ], $this->day('2026-11-20'), 2, 1, 10.0);

        $this->assertSame([10.0, 20.0, 30.0, 0.0, 0.0], $result['trimp']);
    }

    public function testDoesNotCountTheThresholdDistanceAsALongJog(): void
    {
        $result = $this->aggregator()->aggregate([
            $this->activity('2026-11-21', 2.0, 10.0),
            $this->activity('2026-11-21', 2.0, 10.1),
        ], $this->day('2026-11-21'), 0, 0, 10.0);

        $this->assertSame([[10.1], []], $result['longjogs']);
        $this->assertSame([20.1, 0.0], $result['distance']);
    }

    public function testSkipsDurationWhenWeightedVo2maxIsZero(): void
    {
        $result = $this->aggregator()->aggregate([
            $this->activity('2026-11-21', 1.0, 2.0, 0.0, 60.0),
            $this->activity('2026-11-21', 1.0, 2.0, 100.0, 20.0),
        ], $this->day('2026-11-21'), 0, 0, 10.0);

        $this->assertSame([100.0, 0.0], $result['vo2max_weighted']);
        $this->assertSame([20.0, 0.0], $result['vo2max_sum_time']);
    }

    public function testReturnsZeroFilledArraysForMissingTraining(): void
    {
        $result = $this->aggregator()->aggregate([], $this->day('2026-11-21'), 2, 1, 10.0);

        $this->assertSame([0.0, 0.0, 0.0, 0.0, 0.0], $result['trimp']);
        $this->assertSame([0.0, 0.0, 0.0, 0.0, 0.0], $result['distance']);
        $this->assertSame([[], [], [], [], []], $result['longjogs']);
    }

    /**
     * @return array{day: string, trimp: float, distance: float, vo2max_weighted: float, vo2max_sum_time: float, is_running: bool}
     */
    private function activity(
        string $day,
        float $trimp,
        float $distance = 0.0,
        float $weightedVo2max = 0.0,
        float $vo2maxSumTime = 0.0,
        bool $isRunning = true
    ): array {
        return [
            'day' => $day,
            'trimp' => $trimp,
            'distance' => $distance,
            'vo2max_weighted' => $weightedVo2max,
            'vo2max_sum_time' => $vo2maxSumTime,
            'is_running' => $isRunning,
        ];
    }

    private function day(string $date): DateTimeImmutable
    {
        return new DateTimeImmutable($date, new DateTimeZone('UTC'));
    }

    private function aggregator(): RechenspieleShapeDailyAggregator
    {
        return new RechenspieleShapeDailyAggregator();
    }
}
