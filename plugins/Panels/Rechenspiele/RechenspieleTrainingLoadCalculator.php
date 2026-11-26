<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele;

use Runalyze\Sports\Performance\Model\TsbModel;
use Runalyze\Sports\Performance\Monotony;

final readonly class RechenspieleTrainingLoadCalculator
{
    /**
     * @param array<int, float> $modelTrimp
     * @param list<float> $recentTrimp
     * @return array{
     *     atl: int,
     *     ctl: int,
     *     tsb: int,
     *     atl_percent: int,
     *     ctl_percent: int,
     *     tsb_percent: int,
     *     max_atl: int,
     *     max_ctl: int,
     *     rest_days: int,
     *     easy_trimp: int,
     *     monotony: float,
     *     monotony_percent: float,
     *     training_strain: int,
     *     training_strain_percent: float
     * }
     */
    public function calculate(
        array $modelTrimp,
        array $recentTrimp,
        int $ctlDays,
        int $atlDays,
        int $maxAtl,
        int $maxCtl
    ): array {
        $model = new TsbModel($modelTrimp, $ctlDays, $atlDays);
        $model->calculate();

        $maxAtl = max(1, $maxAtl, $model->maxFatigue());
        $maxCtl = max(1, $maxCtl, $model->maxFitness());

        $atl = $model->fatigueAt(0);
        $ctl = $model->fitnessAt(0);
        $tsb = $model->performanceAt(0);
        $tsbPercent = (int) round(100 * $tsb / max(1, $atl, $ctl));

        $monotony = new Monotony($recentTrimp, 2.0 * $maxAtl);
        $monotony->calculate();

        return [
            'atl' => $atl,
            'ctl' => $ctl,
            'tsb' => $tsb,
            'atl_percent' => (int) round(100 * $atl / $maxAtl),
            'ctl_percent' => (int) round(100 * $ctl / $maxCtl),
            'tsb_percent' => $tsbPercent,
            'max_atl' => $maxAtl,
            'max_ctl' => $maxCtl,
            'rest_days' => (int) ceil($model->restDays($ctl, $atl)),
            'easy_trimp' => (int) ceil($model->maxTrimpToBalanced($ctl, $atl)),
            'monotony' => $monotony->value(),
            'monotony_percent' => $monotony->valueAsPercentage(),
            'training_strain' => (int) round($monotony->trainingStrain()),
            'training_strain_percent' => $monotony->trainingStrainAsPercentage(),
        ];
    }
}
