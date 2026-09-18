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

        return mktime(23, 59, 50, (int) date('m', $start), (int) date('d', $start) + 6, (int) date('Y', $start));
    }

    /**
     * Get the name of a day
     * @param string $w     date('w');
     * @param bool $short   short version, default: false
     * @codeCoverageIgnore
     */
    public static function weekday(string $w, bool $short = false): string
    {
        return match ((int) $w % 7) {
            0 => $short ? __('Sun') : __('Sunday'),
            1 => $short ? __('Mon') : __('Monday'),
            2 => $short ? __('Tue') : __('Tuesday'),
            3 => $short ? __('Wed') : __('Wednesday'),
            4 => $short ? __('Thu') : __('Thursday'),
            5 => $short ? __('Fri') : __('Friday'),
            default => $short ? __('Sat') : __('Saturday'),
        };
    }

    /**
     * Get the name of the month
     * @param string $m     date('m');
     * @param bool $short   short version, default: false
     * @codeCoverageIgnore
     */
    public static function month(string $m, bool $short = false): string
    {
        return match ((int) $m % 12) {
            1 => $short ? __('Jan') : __('January'),
            2 => $short ? __('Feb') : __('February'),
            3 => $short ? __('Mar') : __('March'),
            4 => $short ? __('Apr') : __('April'),
            5 => $short ? __('May') : __('May'),
            6 => $short ? __('Jun') : __('June'),
            7 => $short ? __('Jul') : __('July'),
            8 => $short ? __('Aug') : __('August'),
            9 => $short ? __('Sep') : __('September'),
            10 => $short ? __('Oct') : __('October'),
            11 => $short ? __('Nov') : __('November'),
            default => $short ? __('Dec') : __('December'),
        };
    }
}
