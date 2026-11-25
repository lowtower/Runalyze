<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele;

use InvalidArgumentException;
use Runalyze\Sports\Performance\Model\BanisterModel;
use Runalyze\Sports\Performance\Model\TsbModel;

/**
 * Calculates shape plot model series without database or rendering dependencies.
 */
final readonly class RechenspieleShapeSeriesCalculator
{
    /**
     * @param list<float> $trimp
     * @param list<float> $weightedVo2max
     * @param list<float> $vo2maxDurations
     * @return array{
     *     atl: array<int, float>,
     *     ctl: array<int, float>,
     *     tsb: array<int, float>,
     *     trimp: array<int, float>,
     *     vo2max: array<int, float>,
     *     vo2max_day: array<int, float>,
     *     max_atl: int,
     *     max_ctl: int,
     *     recomputed_maxima: array{atl: int, ctl: int}|null,
     *     max_trimp: float,
     *     show_in_percent: bool
     * }
     *
     * @throws InvalidArgumentException for unsupported models, incomplete data or invalid parameters
     */
    public function calculate(
        array $trimp,
        array $weightedVo2max,
        array $vo2maxDurations,
        string $model,
        int $ctlDays,
        int $atlDays,
        int $vo2maxDays,
        int $lowestIndex,
        int $highestIndex,
        int $storedMaxAtl,
        int $storedMaxCtl,
        bool $useCalculatedMaxima,
        bool $showInPercent,
        float $vo2maxCorrectionFactor
    ): array {
        $count = count($trimp);

        if (
            ! in_array($model, RechenspielePlotSelection::availableModels(), true)
            || $ctlDays < 1
            || $atlDays < 1
            || $vo2maxDays < 1
            || $lowestIndex < 0
            || $highestIndex < $lowestIndex
            || $highestIndex >= $count
            || count($weightedVo2max) !== $count
            || count($vo2maxDurations) !== $count
        ) {
            throw new InvalidArgumentException('Invalid shape plot model input.');
        }

        $performanceModel = $model === 'banister'
            ? new BanisterModel($trimp, $ctlDays, $atlDays, 1, 3)
            : new TsbModel($trimp, $ctlDays, $atlDays);

        $performanceModel->calculate();

        $maxAtl = $useCalculatedMaxima ? $performanceModel->maxFatigue() : $storedMaxAtl;
        $maxCtl = $useCalculatedMaxima ? $performanceModel->maxFitness() : $storedMaxCtl;
        $recomputedMaxima = $useCalculatedMaxima && $model === 'tsb'
            ? [
                'atl' => $maxAtl,
                'ctl' => $maxCtl,
            ]
            : null;
        $percent = $showInPercent && $model !== 'banister';

        if (! $percent) {
            $maxAtl = 100;
            $maxCtl = 100;
        }

        $maxAtl = max(1, $maxAtl);
        $maxCtl = max(1, $maxCtl);

        $atl = [];
        $ctl = [];
        $tsb = [];
        $trimps = [];
        $vo2max = [];
        $vo2maxDay = [];
        $maxTrimp = 0.0;

        for ($index = $lowestIndex; $index <= $highestIndex; ++$index) {
            $atl[$index] = 100.0 * $performanceModel->fatigueAt($index) / $maxAtl;
            $ctl[$index] = 100.0 * $performanceModel->fitnessAt($index) / $maxCtl;
            $tsb[$index] = 100.0 * $performanceModel->performanceAt($index) / $maxCtl;
            $trimps[$index] = $trimp[$index];
            $maxTrimp = max($maxTrimp, $trimp[$index]);

            $firstDay = max(0, $index - $vo2maxDays);
            $weightedSum = array_sum(array_slice($weightedVo2max, $firstDay, $index - $firstDay));
            $durationSum = array_sum(array_slice($vo2maxDurations, $firstDay, $index - $firstDay));

            if ($durationSum > 0.0) {
                $vo2max[$index] = $vo2maxCorrectionFactor * $weightedSum / $durationSum;
            }

            if ($weightedVo2max[$index] != 0.0 && $vo2maxDurations[$index] > 0.0) {
                $vo2maxDay[$index] = $vo2maxCorrectionFactor * $weightedVo2max[$index] / $vo2maxDurations[$index];
            }
        }

        return [
            'atl' => $atl,
            'ctl' => $ctl,
            'tsb' => $tsb,
            'trimp' => $trimps,
            'vo2max' => $vo2max,
            'vo2max_day' => $vo2maxDay,
            'max_atl' => $maxAtl,
            'max_ctl' => $maxCtl,
            'recomputed_maxima' => $recomputedMaxima,
            'max_trimp' => $maxTrimp,
            'show_in_percent' => $percent,
        ];
    }
}
