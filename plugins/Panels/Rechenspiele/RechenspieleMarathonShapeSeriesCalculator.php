<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele;

use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\BasicEndurance;
use Runalyze\Sports\Running\MarathonShape;

/**
 * Calculates historical marathon shape percentages from daily running history.
 */
final readonly class RechenspieleMarathonShapeSeriesCalculator
{
    /**
     * @param list<float> $distances
     * @param list<list<float>> $longjogs
     * @param array<int, float> $rollingVo2max
     * @return array<int, int>
     *
     * @throws InvalidArgumentException if the supplied history or window is invalid
     */
    public function calculate(
        array $distances,
        array $longjogs,
        array $rollingVo2max,
        BasicEndurance $configuration,
        int $firstIndex,
        int $lastIndex,
        int $numberOfDaysSinceFirstActivity
    ): array {
        $count = count($distances);
        $longJogDays = $configuration->getDaysToConsiderForLongJogs();
        $weekKmDays = $configuration->getDaysToConsiderForWeeklyMileage($numberOfDaysSinceFirstActivity);
        $minimumLongJogKm = $configuration->getMinimalDistanceForLongJog();

        if (
            $count !== count($longjogs)
            || $firstIndex < 0
            || $lastIndex < $firstIndex
            || $lastIndex >= $count
            || $longJogDays < 1
            || $weekKmDays < 1
            || $numberOfDaysSinceFirstActivity < 0
        ) {
            throw new InvalidArgumentException('Invalid marathon shape plot history or time window.');
        }

        $shape = new MarathonShape(0.0, $configuration);
        $series = [];

        for ($day = $firstIndex; $day <= $lastIndex; ++$day) {
            if (! isset($rollingVo2max[$day])) {
                continue;
            }

            $shape->setEffectiveVO2max($rollingVo2max[$day]);
            $distance = array_sum(array_slice($distances, max(0, $day - $weekKmDays), $weekKmDays));
            $longJogPoints = 0.0;

            for ($age = 0; $age <= $longJogDays; ++$age) {
                $index = $day - $age;

                if ($index < 0) {
                    break;
                }

                foreach ($longjogs[$index] as $longJogDistance) {
                    $longJogPoints += 2 * (1 - $age / $longJogDays)
                        * ($longJogDistance - $minimumLongJogKm) ** 2;
                }
            }

            $target = $shape->getTargetForLongJogEachWeek() - $minimumLongJogKm;

            if ($target <= 0.0) {
                throw new InvalidArgumentException('The configured long jog target must exceed the minimum distance.');
            }

            $series[$day] = $shape->getShapeFor(
                $distance,
                $longJogPoints / ($target ** 2),
                $numberOfDaysSinceFirstActivity
            );
        }

        return $series;
    }
}
