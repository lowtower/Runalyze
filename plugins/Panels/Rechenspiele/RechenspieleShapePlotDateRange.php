<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Defines the date boundaries and warmup indices for the legacy shape plot.
 */
final readonly class RechenspieleShapePlotDateRange
{
    /**
     * @return array{
     *     start_day: DateTimeImmutable,
     *     end_day: DateTimeImmutable,
     *     warmup_days: int,
     *     lowest_index: int,
     *     highest_index: int,
     *     number_of_days: int,
     *     calculate_maxima: bool,
     *     limited_year: ?int,
     *     mark_future: bool
     * }
     *
     * @throws InvalidArgumentException if a selected range or day count is invalid
     */
    public function resolve(
        string $range,
        DateTimeImmutable $firstActivityDay,
        DateTimeImmutable $today,
        int $atlDays,
        int $ctlDays,
        int $vo2maxDays,
        int $weekKmDays,
        int $longJogDays
    ): array {
        if (
            min($atlDays, $ctlDays, $vo2maxDays, $weekKmDays, $longJogDays) < 1
            || ! in_array($range, ['all', 'lasthalf', 'lastyear'], true)
                && ! preg_match('/^[0-9]{4}$/D', $range)
        ) {
            throw new InvalidArgumentException('Invalid shape plot range or training history window.');
        }

        $timezone = $today->getTimezone();
        $currentDay = new DateTimeImmutable($today->format('Y-m-d'), $timezone);
        $firstDay = new DateTimeImmutable($firstActivityDay->setTimezone($timezone)->format('Y-m-d'), $timezone);
        $year = (int) $currentDay->format('Y');
        $firstYear = (int) $firstDay->format('Y');

        if ($firstDay > $currentDay || (! in_array($range, ['all', 'lasthalf', 'lastyear'], true)
            && ((int) $range < $firstYear || (int) $range > $year))
        ) {
            throw new InvalidArgumentException('The selected shape plot period lies outside the available history.');
        }

        $allYears = $range === 'all';
        $lastHalfYear = $range === 'lasthalf';
        $lastYear = $range === 'lastyear';

        if ($allYears) {
            $startDay = $firstDay;
            $endDay = $currentDay;
        } elseif ($lastHalfYear) {
            $startDay = $currentDay->modify('-180 days');
            $endDay = $currentDay->modify('+30 days');
        } elseif ($lastYear) {
            $startDay = $currentDay->modify('-1 year');
            $endDay = $currentDay->modify('+30 days');
        } else {
            $startDay = new DateTimeImmutable($range . '-01-01', $timezone);
            $endDay = new DateTimeImmutable($range . '-12-31', $timezone);
        }

        $warmupDays = max(3 * max($atlDays, $ctlDays, $vo2maxDays), $weekKmDays, $longJogDays);
        $numberOfDays = (int) $startDay->diff($endDay)->format('%a');
        $lowestIndex = $warmupDays + (int) ! $allYears;

        return [
            'start_day' => $startDay,
            'end_day' => $endDay,
            'warmup_days' => $warmupDays,
            'lowest_index' => $lowestIndex,
            'highest_index' => $lowestIndex + $numberOfDays,
            'number_of_days' => $numberOfDays,
            'calculate_maxima' => $allYears,
            'limited_year' => $allYears || $lastHalfYear || $lastYear ? null : (int) $range,
            'mark_future' => $lastHalfYear || $lastYear || $endDay->format('Y') === $currentDay->format('Y'),
        ];
    }
}
