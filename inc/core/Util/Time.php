<?php

/**
 * This file contains class::Time
 * @package Runalyze\Util
 */

namespace Runalyze\Util;

use Runalyze\Configuration;

/**
 * Class for standard operations for timestamps
 *
 * @author Hannes Christiansen
 * @package Runalyze\Util
 */
class Time
{
    /**
     * Absolute difference in days between two timestamps
     * @param int $time_2 optional
     */
    public static function diffInDays(int $time_1, int $time_2 = 0): float
    {
        if ($time_2 === 0) {
            $time_2 = time();
        }

        return floor(abs(($time_1 - $time_2) / (3600 * 24)));
    }

    /**
     * Calculates the difference in days of two dates (YYYY-mm-dd)
     */
    public static function diffOfDates(string $date1, string $date2): int
    {
        return (int) date_diff(date_create($date1), date_create($date2))
            ->format('%a');
    }

    /**
     * Is given timestamp from today?
     */
    public static function isToday(int $timestamp): bool
    {
        return date('d.m.Y') === date('d.m.Y', $timestamp);
    }

    /**
     * Get the timestamp of the start of the week
     */
    public static function weekstart(int $time): int
    {
        $w = date('w', $time);

        if (Configuration::General()->weekStart()->isMonday()) {
            if ($w == 0) {
                $w = 6;
            } else {
                --$w;
            }
        }

        return mktime(0, 0, 0, (int) date('m', $time), (int) date('d', $time) - $w, (int) date('Y', $time));
    }

    /**
     * Get the timestamp of the end of the week
     */
    public static function weekend(int $time): int
    {
        $start = self::weekstart($time);

        return mktime(23, 59, 50, date('m', $start), date('d', $start) + 6, date('Y', $start));
    }

    /**
     * Get the name of a day
     * @param string $w     date('w');
     * @param bool $short   short version, default: false
     * @codeCoverageIgnore
     */
    public static function weekday(string $w, bool $short = false): string
    {
        switch ((int) $w % 7) {
            case 0: return $short ? __('Sun') : __('Sunday');
            case 1: return $short ? __('Mon') : __('Monday');
            case 2: return $short ? __('Tue') : __('Tuesday');
            case 3: return $short ? __('Wed') : __('Wednesday');
            case 4: return $short ? __('Thu') : __('Thursday');
            case 5: return $short ? __('Fri') : __('Friday');
            case 6:
            default: return $short ? __('Sat') : __('Saturday');
        }
    }

    /**
     * Get the name of the month
     * @param string $m     date('m');
     * @param bool $short   short version, default: false
     * @codeCoverageIgnore
     */
    public static function month(string $m, bool $short = false): string
    {
        switch ((int) $m % 12) {
            case 1: return $short ? __('Jan') : __('January');
            case 2: return $short ? __('Feb') : __('February');
            case 3: return $short ? __('Mar') : __('March');
            case 4: return $short ? __('Apr') : __('April');
            case 5: return $short ? __('May') : __('May');
            case 6: return $short ? __('Jun') : __('June');
            case 7: return $short ? __('Jul') : __('July');
            case 8: return $short ? __('Aug') : __('August');
            case 9: return $short ? __('Sep') : __('September');
            case 10: return $short ? __('Oct') : __('October');
            case 11: return $short ? __('Nov') : __('November');
            case 0:
            default: return $short ? __('Dec') : __('December');
        }
    }
}
