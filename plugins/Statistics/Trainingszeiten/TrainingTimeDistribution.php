<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Trainingszeiten;

use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use SplPriorityQueue;

/**
 * @phpstan-import-type TrainingTimeSample from TrainingRepository
 * @phpstan-type NightActivity array{id: int, time: int, sportId: int, duration: float, distance: float, isTrack: bool, shortSport: bool, sportIcon: string, minuteOfDay: int}
 */
final class TrainingTimeDistribution
{
    private const int SECONDS_PER_HOUR = 3600;

    private const int SECONDS_PER_DAY = 86400;

    private const int MINUTES_PER_DAY = 1440;

    private const int NIGHTLY_LIMIT = 20;

    /**
     * @param array<int, string> $sports
     * @param iterable<TrainingTimeSample> $activities
     * @return array{daytime: array<int, non-empty-array<int, float>>, weekday: array<int, non-empty-array<int, float>>, nights: list<NightActivity>}
     */
    public function summarize(array $sports, iterable $activities, bool $showNights, bool $includeShortSports): array
    {
        $daytime = array_fill_keys(array_keys($sports), array_fill(0, 24, 0.0));
        $weekday = array_fill_keys(array_keys($sports), array_fill(0, 7, 0.0));
        /** @var SplPriorityQueue<array{int, int, int}, NightActivity> $nightActivities */
        $nightActivities = new SplPriorityQueue();

        foreach ($activities as $activity) {
            $sportId = $activity['sportId'];
            if (! isset($sports[$sportId])) {
                continue;
            }

            $duration = max(0.0, $activity['duration']);
            $weekday[$sportId][(int) gmdate('N', $activity['time']) - 1] += $duration / self::SECONDS_PER_HOUR;

            // The activity timestamp already represents the local wall clock.
            $seconds = ($activity['time'] % self::SECONDS_PER_DAY + self::SECONDS_PER_DAY) % self::SECONDS_PER_DAY;
            $minuteOfDay = intdiv($seconds, 60);
            if ($minuteOfDay === 0) {
                continue;
            }

            $this->addDaytime($daytime[$sportId], $seconds, $duration);
            if ($showNights && ($includeShortSports || ! $activity['shortSport'])) {
                $distanceFromTwo = abs($minuteOfDay - 120);
                $nightActivities->insert($activity + [
                    'minuteOfDay' => $minuteOfDay,
                ], [
                    min($distanceFromTwo, self::MINUTES_PER_DAY - $distanceFromTwo),
                    -($minuteOfDay % 60),
                    $activity['id'],
                ]);

                // Keep only the nearest activities while streaming the result.
                if ($nightActivities->count() > self::NIGHTLY_LIMIT) {
                    $nightActivities->extract();
                }
            }
        }

        $nights = iterator_to_array($nightActivities, false);
        usort($nights, static fn (array $a, array $b): int => [
            ($a['minuteOfDay'] + 720) % self::MINUTES_PER_DAY,
            $a['id'],
        ] <=> [
            ($b['minuteOfDay'] + 720) % self::MINUTES_PER_DAY,
            $b['id'],
        ]);

        return [
            'daytime' => $daytime,
            'weekday' => $weekday,
            'nights' => $nights,
        ];
    }

    /**
     * @param non-empty-array<int, float> $hours
     */
    private function addDaytime(array &$hours, int $seconds, float $duration): void
    {
        $wholeDays = floor($duration / self::SECONDS_PER_DAY);
        if ($wholeDays > 0) {
            foreach ($hours as $hour => $current) {
                $hours[$hour] = $current + $wholeDays;
            }
        }

        $remaining = fmod($duration, self::SECONDS_PER_DAY);
        $hour = intdiv($seconds, self::SECONDS_PER_HOUR);
        $available = self::SECONDS_PER_HOUR - $seconds % self::SECONDS_PER_HOUR;
        while ($remaining > 0) {
            $chunk = min($remaining, $available);
            $hours[$hour] += $chunk / self::SECONDS_PER_HOUR;
            $remaining -= $chunk;
            $hour = ($hour + 1) % 24;
            $available = self::SECONDS_PER_HOUR;
        }
    }
}
