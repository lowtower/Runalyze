<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Goals;

use DateTime;
use DateTimeImmutable;

/**
 * Calendar ranges used by the Goals panel.
 */
final readonly class GoalsTimeRangeProvider
{
    /**
     * @return array<string, array{name: string, start: DateTime, end: DateTime, note?: string}>
     */
    public function getRanges(DateTimeImmutable $now, bool $weekStartsOnMonday): array
    {
        $timezone = $now->getTimezone();
        $today = DateTime::createFromImmutable($now)->setTime(0, 0);
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        $weekday = (int) $now->format('w');
        $daysSinceWeekStart = $weekStartsOnMonday ? ($weekday + 6) % 7 : $weekday;
        $weekStart = (clone $today)->modify(sprintf('-%d days', $daysSinceWeekStart));
        $weekEnd = (clone $weekStart)->modify('+6 days')->setTime(23, 59, 50);

        $seasonStart = (new DateTime('now', $timezone))->setTime(0, 0)->setISODate($year, 27, 1);
        $seasonEnd = (new DateTime('now', $timezone))->setTime(0, 0);

        if ($seasonStart > $now) {
            $seasonStart->setISODate($year, 1, 1);
            $seasonEnd->setISODate($year, 26, 7);
        } else {
            $lastWeek = (int) (new DateTimeImmutable($year . '-12-31', $timezone))->format('W');
            $seasonEnd->setISODate($year, $lastWeek === 53 ? 53 : 52, 7);
        }

        return [
            'woche' => [
                'name' => __('Week'),
                'start' => $weekStart,
                'end' => $weekEnd,
            ],
            'mon' => [
                'name' => __('Month'),
                'start' => new DateTime($now->format('Y-m-01'), $timezone),
                'end' => new DateTime($now->format('Y-m-t'), $timezone),
            ],
            'hj' => [
                'name' => __('Half-Year'),
                'start' => new DateTime($year . ($month < 7 ? '-01-01' : '-07-01'), $timezone),
                'end' => new DateTime($year . ($month < 7 ? '-06-30' : '-12-31'), $timezone),
            ],
            'saison' => [
                'name' => __('Season'),
                'start' => $seasonStart,
                'end' => $seasonEnd,
                'note' => __('Note: Season means the current season in the german &quot;kmspiel&quot;'),
            ],
            'jahr' => [
                'name' => __('Year'),
                'start' => new DateTime($year . '-01-01', $timezone),
                'end' => new DateTime($year . '-12-31', $timezone),
            ],
        ];
    }
}
