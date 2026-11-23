<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose;

use Runalyze\Calculation\BasicEndurance;
use Runalyze\Sports\Running\Prognosis\VO2max;

/**
 * Calculates the historical prognosis and marathon-shape series from
 * account-scoped training rows. The caller supplies the configured endurance
 * model and the current date; this class never reads account globals.
 */
final readonly class PrognoseTrendSeriesCalculator
{
    /**
     * @param list<array{date_age: int, date: string, distance: float, vo2max_weighted: float, vo2max_sum_time: float}> $history
     * @return array{
     *     prognosis: array<string, float>,
     *     prognosis_with_basic_endurance: array<string, float>
     * }
     */
    public function calculate(
        array $history,
        float $distance,
        BasicEndurance $endurance,
        float $vo2maxFactor,
        int $vo2maxDays,
        string $today
    ): array {
        $prognosis = [];
        $withEndurance = [];

        if ($history === []) {
            return [
                'prognosis' => $prognosis,
                'prognosis_with_basic_endurance' => $withEndurance,
            ];
        }

        $firstVO2max = 0;
        $firstKm = 0;
        $firstLongJog = 0;
        $weightedSum = 0.0;
        $timeSum = 0.0;
        $kmSum = 0.0;

        $kmDays = $endurance->getDaysForWeekKm();
        $longJogDays = $endurance->getDaysToRecognizeForLongjogs();
        $minimalLongJogKm = $endurance->getMinimalDistanceForLongjogs();

        if ($history[count($history) - 1]['date_age'] < 0) {
            $history[] = [
                'date_age' => 0,
                'date' => $today,
                'distance' => 0.0,
                'vo2max_weighted' => 0.0,
                'vo2max_sum_time' => 0.0,
            ];
        }

        $model = new VO2max();

        foreach ($history as $index => $current) {
            while ($firstVO2max < $index && $vo2maxDays <= $current['date_age'] - $history[$firstVO2max]['date_age']) {
                $weightedSum -= $history[$firstVO2max]['vo2max_weighted'];
                $timeSum -= $history[$firstVO2max]['vo2max_sum_time'];
                ++$firstVO2max;
            }

            while ($firstKm < $index && $kmDays <= $current['date_age'] - $history[$firstKm]['date_age']) {
                $kmSum -= $history[$firstKm]['distance'];
                ++$firstKm;
            }

            while ($firstLongJog < $index && $longJogDays <= $current['date_age'] - $history[$firstLongJog]['date_age']) {
                ++$firstLongJog;
            }

            $weightedSum += $current['vo2max_weighted'];
            $timeSum += $current['vo2max_sum_time'];
            $kmSum += $current['distance'];
            $effectiveVO2max = $timeSum > 0 ? $vo2maxFactor * $weightedSum / $timeSum : 0.0;

            if ($effectiveVO2max <= VO2max::REASONABLE_VO2MAX_MINIMUM || $effectiveVO2max >= VO2max::REASONABLE_VO2MAX_MAXIMUM) {
                continue;
            }

            $longJogPoints = 0.0;
            for ($i = $firstLongJog; $i <= $index; ++$i) {
                $longJogPoints += 2 * (1 - ($current['date_age'] - $history[$i]['date_age']) / $longJogDays)
                    * max(0, $history[$i]['distance'] - $minimalLongJogKm) ** 2;
            }

            $endurance->setEffectiveVO2max($effectiveVO2max);
            $longJogPoints /= $endurance->getTargetLongjogKmPerWeek() ** 2;
            $marathonShape = $endurance->asArray(0, [
                'km' => $kmSum,
                'sum' => $longJogPoints,
            ])['percentage'];

            $model->setEffectiveVO2max($effectiveVO2max);
            $model->setMarathonShape($marathonShape);
            $model->adjustForMarathonShape(true);
            $secondsWithEndurance = $model->getSeconds($distance);

            if ($secondsWithEndurance !== null && $secondsWithEndurance > 0) {
                $timestamp = strtotime($current['date'] . ' 12:00');
                if ($timestamp === false) {
                    continue;
                }

                $plotIndex = $timestamp . '000';
                $withEndurance[$plotIndex] = $secondsWithEndurance * 1000;

                $model->adjustForMarathonShape(false);
                $seconds = $model->getSeconds($distance);
                if ($seconds !== null) {
                    $prognosis[$plotIndex] = $seconds * 1000;
                }
            }
        }

        return [
            'prognosis' => $prognosis,
            'prognosis_with_basic_endurance' => $withEndurance,
        ];
    }
}
