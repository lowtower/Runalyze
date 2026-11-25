<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Wettkampf;

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

final class RaceYearComparison
{
    /**
     * @param list<array{time: int, officialDistance: ?float, officialTime: float, ...}> $races
     * @param list<float> $distances
     * @return array{totals: array<int, int>, distances: list<array{label: string, cells: array<int, array{count: int, time: string, best: bool}>}>}|null
     */
    public static function table(array $races, array $distances, DistanceUnitSystem $units): ?array
    {
        if ($races === []) {
            return null;
        }

        $totals = [];
        $counts = [];
        $yearlyBest = [];
        $overallBest = [];
        foreach ($races as $race) {
            $year = (int) gmdate('Y', $race['time']);
            $totals[$year] = ($totals[$year] ?? 0) + 1;
            $distance = (string) ($race['officialDistance'] ?? 0);
            $seconds = $race['officialTime'];
            $counts[$distance][$year] = ($counts[$distance][$year] ?? 0) + 1;
            $yearlyBest[$distance][$year] = min($yearlyBest[$distance][$year] ?? INF, $seconds);
            $overallBest[$distance] = min($overallBest[$distance] ?? INF, $seconds);
        }

        ksort($totals);
        $rows = [];
        foreach ($distances as $distance) {
            $key = (string) $distance;
            $cells = [];
            foreach ($yearlyBest[$key] ?? [] as $year => $seconds) {
                $cells[$year] = [
                    'count' => $counts[$key][$year],
                    'time' => Duration::format($seconds),
                    'best' => $seconds === $overallBest[$key],
                ];
            }

            $rows[] = [
                'label' => new Distance($distance, $units)->stringAuto(true, 1),
                'cells' => $cells,
            ];
        }

        return [
            'totals' => $totals,
            'distances' => $rows,
        ];
    }
}
