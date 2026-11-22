<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose;

use Ajax;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Sports\Running\Prognosis\PrognosisInterface;
use Runalyze\Sports\Running\VO2max\EffectiveVO2max;
use Runalyze\Util\LocalTime;

final readonly class PrognoseCalculatorPredictionRows
{
    public function __construct(private PrognoseCalculatorPersonalBestProvider $personalBestProvider) {}

    /**
     * @param list<float|string> $distances Distances in kilometers
     *
     * @return list<array<string, string>>
     */
    public function calculate(
        Account $account,
        int $runningSportId,
        array $distances,
        PrognosisInterface $prognosis,
        int $legacyPaceUnit,
        DistanceUnitSystem $distanceUnitSystem
    ): array {
        if (! $prognosis->areValuesValid()) {
            return [];
        }

        $personalBests = $this->personalBestProvider->findForDistances($account, $runningSportId, $distances);
        $rows = [];

        foreach ($distances as $kilometers) {
            $distance = (float) $kilometers;
            $seconds = $prognosis->getSeconds($distance) ?? 0.0;
            $personalBest = $personalBests[sprintf('%.2f', $distance)] ?? null;
            $personalBestSeconds = $personalBest['seconds'] ?? 0.0;

            $predictionVO2max = new EffectiveVO2max();
            $predictionVO2max->fromPace($distance, (int) $seconds);

            $personalBestVO2max = new EffectiveVO2max();
            $personalBestVO2max->fromPace($distance, (int) $personalBestSeconds);

            $predictionPace = new Pace($seconds, $distance, $legacyPaceUnit);
            $personalBestPace = new Pace($personalBestSeconds, $distance, $legacyPaceUnit);

            $personalBestDate = $personalBest !== null
                ? Ajax::trainingLink(
                    $personalBest['activity_id'],
                    new LocalTime($personalBest['timestamp'])->format('d.m.Y'),
                    true
                )
                : '-';

            $rows[] = [
                'distance' => new Distance($distance, $distanceUnitSystem)->stringAuto(),
                'prognosis' => $seconds > 0 ? Duration::format($seconds) : '-',
                'prognosis-pace' => $predictionPace->valueWithAppendix(),
                'prognosis-vo2max' => $seconds > 0 ? $predictionVO2max->uncorrectedValue() : '-',
                'diff' => $personalBest === null || $seconds == 0
                    ? '-'
                    : ($personalBestSeconds > $seconds ? '+ ' : '- ')
                        . Duration::format(abs(round($personalBestSeconds - $seconds))),
                'diff-class' => $personalBestSeconds > $seconds ? 'plus' : 'minus',
                'pb' => $personalBestSeconds > 0 ? Duration::format($personalBestSeconds) : '-',
                'pb-pace' => $personalBestSeconds > 0 ? $personalBestPace->valueWithAppendix() : '-',
                'pb-vo2max' => $personalBestSeconds > 0 ? $personalBestVO2max->uncorrectedValue() : '-',
                'pb-date' => $personalBestDate,
            ];
        }

        return $rows;
    }
}
