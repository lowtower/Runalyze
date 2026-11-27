<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Prognose;

use Ajax;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Bundle\CoreBundle\Component\Activity\PaceFormatter;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Sports\Running\Prognosis\PrognosisInterface;
use Runalyze\Sports\Running\VO2max\EffectiveVO2max;
use Runalyze\Util\LocalTime;

final readonly class PrognoseCalculatorPredictionRows
{
    public function __construct(
        private PrognoseCalculatorPersonalBestProvider $personalBestProvider
    ) {}

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
        AbstractPaceUnit $paceUnit,
        DistanceUnitSystem $distanceUnitSystem
    ): array {
        if (! $prognosis->areValuesValid()) {
            return [];
        }

        $personalBests = $this->personalBestProvider->findForDistances($account, $runningSportId, $distances);
        $formatter = new PaceFormatter($paceUnit);
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

            $personalBestDate = $personalBest !== null
                ? Ajax::trainingLink(
                    $personalBest['activity_id'],
                    new LocalTime($personalBest['timestamp'])->format('d.m.Y'),
                    true
                )
                : '-';

            $formattedDistance = str_replace(
                '&nbsp;',
                "\u{00A0}",
                new Distance($distance, $distanceUnitSystem)->stringAuto()
            );

            if (str_ends_with($formattedDistance, 'm') && ! str_ends_with($formattedDistance, 'km')) {
                $formattedDistance = substr($formattedDistance, 0, -1) . "\u{00A0}m";
            }

            $rows[] = [
                'distance' => $formattedDistance,
                'prognosis' => $seconds > 0 ? Duration::format($seconds) : '-',
                'prognosis-pace' => $formatter->format(PaceFormatter::secondsPerKilometer($seconds, $distance)),
                'prognosis-vo2max' => $seconds > 0 ? $predictionVO2max->uncorrectedValue() : '-',
                'diff' => $personalBest === null || $seconds == 0
                    ? '-'
                    : ($personalBestSeconds > $seconds ? '+ ' : '- ')
                        . Duration::format(abs(round($personalBestSeconds - $seconds))),
                'diff-class' => $personalBestSeconds > $seconds ? 'plus' : 'minus',
                'pb' => $personalBestSeconds > 0 ? Duration::format($personalBestSeconds) : '-',
                'pb-pace' => $personalBestSeconds > 0
                    ? $formatter->format(PaceFormatter::secondsPerKilometer($personalBestSeconds, $distance))
                    : '-',
                'pb-vo2max' => $personalBestSeconds > 0 ? $personalBestVO2max->uncorrectedValue() : '-',
                'pb-date' => $personalBestDate,
            ];
        }

        return $rows;
    }
}
