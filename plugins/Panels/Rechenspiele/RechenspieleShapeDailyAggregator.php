<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Builds the daily input arrays for the shape plot's performance models.
 */
final readonly class RechenspieleShapeDailyAggregator
{
    /**
     * @param list<array{
     *     day: string,
     *     trimp: float,
     *     distance: float,
     *     vo2max_weighted: float,
     *     vo2max_sum_time: float,
     *     is_running: bool
     * }> $activities
     * @return array{
     *     trimp: list<float>,
     *     distance: list<float>,
     *     vo2max_weighted: list<float>,
     *     vo2max_sum_time: list<float>,
     *     longjogs: list<list<float>>
     * }
     */
    public function aggregate(
        array $activities,
        DateTimeImmutable $startDay,
        int $warmupDays,
        int $numberOfDays,
        float $minimalLongJogDistance
    ): array {
        $length = $numberOfDays + $warmupDays + 2;
        $trimp = array_fill(0, $length, 0.0);
        $distance = array_fill(0, $length, 0.0);
        $vo2maxWeighted = array_fill(0, $length, 0.0);
        $vo2maxSumTime = array_fill(0, $length, 0.0);
        $longjogs = array_fill(0, $length, []);

        $start = new DateTimeImmutable($startDay->format('Y-m-d'), new DateTimeZone('UTC'));

        foreach ($activities as $activity) {
            $day = DateTimeImmutable::createFromFormat('!Y-m-d', $activity['day'], new DateTimeZone('UTC'));

            if ($day === false) {
                continue;
            }

            $index = (int) $start->diff($day)->format('%r%a') + $warmupDays;

            if ($index < 0 || $index >= $length) {
                continue;
            }

            $trimp[$index] += $activity['trimp'];

            if (! $activity['is_running']) {
                continue;
            }

            $distance[$index] += $activity['distance'];

            if ($activity['distance'] > $minimalLongJogDistance) {
                $longjogs[$index][] = $activity['distance'];
            }

            if ($activity['vo2max_weighted'] != 0.0) {
                $vo2maxWeighted[$index] += $activity['vo2max_weighted'];
                $vo2maxSumTime[$index] += $activity['vo2max_sum_time'];
            }
        }

        return [
            'trimp' => $trimp,
            'distance' => $distance,
            'vo2max_weighted' => $vo2maxWeighted,
            'vo2max_sum_time' => $vo2maxSumTime,
            'longjogs' => $longjogs,
        ];
    }
}
