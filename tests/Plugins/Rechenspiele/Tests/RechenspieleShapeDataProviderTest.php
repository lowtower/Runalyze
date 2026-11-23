<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapeDailyAggregator;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapeDataProvider;

#[Group('plugin')]
final class RechenspieleShapeDataProviderTest extends TestCase
{
    public function testFetchesAccountScopedHistoryIncludingWarmupAndAggregatesByDay(): void
    {
        $account = new Account();
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('findShapePlotTrainingData')
            ->with(
                $account,
                7,
                $this->day('2026-11-18')->getTimestamp(),
                $this->day('2026-11-21')->setTime(23, 59, 59)->getTimestamp(),
                true
            )
            ->willReturn([
                $this->activity('2026-11-18', 5.0, 0.0, 0.0, 0.0, false),
                $this->activity('2026-11-20', 8.0, 12.0, 90.0, 30.0, true),
                $this->activity('2026-11-20', 3.0, 5.0, 0.0, 0.0, true),
            ]);

        $result = $this->provider($repository)->getDailyData(
            $account,
            7,
            $this->day('2026-11-20'),
            $this->day('2026-11-21'),
            2,
            10.0,
            true
        );

        $this->assertSame([5.0, 0.0, 11.0, 0.0, 0.0], $result['trimp']);
        $this->assertSame([0.0, 0.0, 17.0, 0.0, 0.0], $result['distance']);
        $this->assertSame([0.0, 0.0, 90.0, 0.0, 0.0], $result['vo2max_weighted']);
        $this->assertSame([0.0, 0.0, 30.0, 0.0, 0.0], $result['vo2max_sum_time']);
        $this->assertSame([[], [], [12.0], [], []], $result['longjogs']);
    }

    public function testUsesCalendarDayBoundariesAcrossDaylightSavingChanges(): void
    {
        $account = new Account();
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('findShapePlotTrainingData')
            ->with(
                $account,
                1,
                $this->day('2026-03-27')->getTimestamp(),
                $this->day('2026-03-30')->setTime(23, 59, 59)->getTimestamp(),
                false
            )
            ->willReturn([]);

        $result = $this->provider($repository)->getDailyData(
            $account,
            1,
            new DateTimeImmutable('2026-03-28 00:00:00', new DateTimeZone('Europe/Berlin')),
            new DateTimeImmutable('2026-03-30 23:00:00', new DateTimeZone('Europe/Berlin')),
            1,
            10.0,
            false
        );

        $this->assertCount(5, $result['trimp']);
        $this->assertSame([0.0, 0.0, 0.0, 0.0, 0.0], $result['trimp']);
    }

    public function testRejectsAnInvertedDateRangeWithoutQueryingDatabase(): void
    {
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->never())->method('findShapePlotTrainingData');

        $this->expectException(InvalidArgumentException::class);

        $this->provider($repository)->getDailyData(
            new Account(),
            1,
            $this->day('2026-11-21'),
            $this->day('2026-11-20'),
            0,
            10.0,
            false
        );
    }

    public function testRejectsNegativeWarmupWithoutQueryingDatabase(): void
    {
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->never())->method('findShapePlotTrainingData');

        $this->expectException(InvalidArgumentException::class);

        $this->provider($repository)->getDailyData(
            new Account(),
            1,
            $this->day('2026-11-20'),
            $this->day('2026-11-21'),
            -1,
            10.0,
            false
        );
    }

    /**
     * @return array{day: string, trimp: float, distance: float, vo2max_weighted: float, vo2max_sum_time: float, is_running: bool}
     */
    private function activity(
        string $day,
        float $trimp,
        float $distance,
        float $weightedVo2max,
        float $vo2maxSumTime,
        bool $running
    ): array {
        return [
            'day' => $day,
            'trimp' => $trimp,
            'distance' => $distance,
            'vo2max_weighted' => $weightedVo2max,
            'vo2max_sum_time' => $vo2maxSumTime,
            'is_running' => $running,
        ];
    }

    private function provider(TrainingRepository $repository): RechenspieleShapeDataProvider
    {
        return new RechenspieleShapeDataProvider($repository, new RechenspieleShapeDailyAggregator());
    }

    private function day(string $day): DateTimeImmutable
    {
        return new DateTimeImmutable($day, new DateTimeZone('UTC'));
    }
}
