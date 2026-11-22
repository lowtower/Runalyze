<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose;

use Runalyze\Activity\Duration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

final readonly class PrognoseCalculatorFormInput
{
    /**
     * @param array<string, mixed> $submitted
     * @param array<int, array<string, int|float|null>> $topResults
     * @param list<float|int> $distances
     *
     * @return array<string, mixed>
     */
    public function prepare(
        array $submitted,
        array $topResults,
        array $distances,
        float $currentShape,
        int $endurance,
        DistanceUnitSystem $unitSystem
    ): array {
        if ($submitted === []) {
            $displayDistances = $unitSystem->isImperial()
                ? array_map(
                    static fn (float|int $distance): float => round($distance * DistanceUnitSystem::MILE_MULTIPLIER, 2),
                    $distances
                )
                : $distances;

            return [
                'model' => 'vo2max',
                'distances' => implode(', ', $displayDistances),
                'vo2max' => $currentShape,
                'endurance' => true,
                'endurance-value' => $endurance,
                'best-result-km' => $topResults[0]['distance'] ?? '5.0',
                'best-result-time' => isset($topResults[0]) ? Duration::format($topResults[0]['s']) : '0:26:00',
                'second-best-result-km' => $topResults[1]['distance'] ?? '10.0',
                'second-best-result-time' => isset($topResults[1]) ? Duration::format($topResults[1]['s']) : '1:00:00',
            ];
        }

        $input = [];
        foreach ([
            'model',
            'distances',
            'vo2max',
            'endurance',
            'endurance-value',
            'best-result-km',
            'best-result-time',
            'second-best-result-km',
            'second-best-result-time',
        ] as $key) {
            if (isset($submitted[$key]) && is_scalar($submitted[$key])) {
                $input[$key] = $submitted[$key];
            }
        }

        foreach (['best-result-km', 'second-best-result-km'] as $key) {
            $distance = (float) str_replace(',', '.', (string) ($input[$key] ?? 0));
            $input[$key] = $unitSystem->isImperial()
                ? round($distance / DistanceUnitSystem::MILE_MULTIPLIER, 2)
                : $distance;
        }

        return $input;
    }
}
