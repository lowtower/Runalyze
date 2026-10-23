<?php

/**
 * This file contains class::WeekStart
 * @package Runalyze\Parameter\Application
 */

namespace Runalyze\Parameter\Application;

use Runalyze\Util\LocalTime;

/**
 * Parameter to set first day of week
 *
 * The internal value of this class is the value returned by date('w') which
 * equals numeric representation of the day of the week based on ISO-8601
 * modulo 7, i.e. 1 for Monday and 0 for Sunday.
 *
 * @author Hannes Christiansen
 * @author Michael Pohl <michael@runalyze.de>
 * @package Runalyze\Parameter\Application
 */
class WeekStart extends \Runalyze\Parameter\Select
{
    /**
     * Monday
     */
    public const int MONDAY = 1;

    /**
     * Saturday
     */
    public const int SATURDAY = 6;

    /**
     * Sunday
     */
    public const int SUNDAY = 0;

    public function __construct(int|string $default = self::MONDAY)
    {
        parent::__construct((string) $default, [
            'options' => [
                self::MONDAY => __('Monday'),
                // Saturday is not supported by MySQL as first day of the week
                // self::SATURDAY => __('Saturday'),
                self::SUNDAY => __('Sunday'),
            ],
        ]);
    }

    public function isMonday(): bool
    {
        return $this->value() == self::MONDAY;
    }

    public function isSaturday(): bool
    {
        return $this->value() == self::SATURDAY;
    }

    public function isSunday(): bool
    {
        return $this->value() == self::SUNDAY;
    }

    public function differenceToMondayInDays(): int
    {
        if ($this->value() == self::SATURDAY) {
            return -2;
        }

        if ($this->value() == self::SUNDAY) {
            return -1;
        }

        return 0;
    }

    /**
     * Get current week start
     * @return string
     */
    public function unit(): mixed
    {
        return $this->value();
    }

    /**
     * @param int|bool $now
     * @param bool $transformToServerTime
     */
    public function phpWeek($now = false, $transformToServerTime = false): int
    {
        if ($now === false) {
            $now = time();
        } elseif ($transformToServerTime) {
            $now = new LocalTime($now)->toServerTimestamp();
        }

        if ($this->isSunday() && date('w', $now) == 0) {
            if (date('W', $now) == 53 || date('W', $now) === date('W', mktime(0, 0, 0, 12, 28, (int) date('Y', $now)))) {
                return 1;
            }

            return date('W', $now) + 1;
        }

        return (int) date('W', $now);
    }

    /**
     * Parameter for `WEEK(date, mode)` in MySQL
     * @see http://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_week
     * @deprecated 3.1.0 Use `mysqlWeek()` instead as it fixes #1941
     */
    public function mysqlParameter(): int
    {
        return match ($this->value()) {
            self::SUNDAY => 6,
            default => 3,
        };
    }

    /**
     * Fixed version to get correct mysql week
     * @see https://github.com/Runalyze/Runalyze/issues/1941
     *
     * @param string $dateString
     * @return string Query part to select correct week number
     */
    public function mysqlWeek($dateString): string
    {
        if ($this->isSunday()) {
            return 'WEEK(DATE_ADD(' . $dateString . ', INTERVAL 1 DAY), 3)';
        }

        return 'WEEK(' . $dateString . ', 3)';
    }

    /**
     * @param string $dateString
     */
    public function mysqlYearWeek($dateString): string
    {
        return 'YEAR' . $this->mysqlWeek($dateString);
    }

    public function firstDayOfWeekForStrtotime(): string
    {
        return match ($this->value()) {
            self::SUNDAY => 'sunday',
            self::SATURDAY => 'saturday',
            default => 'monday',
        };
    }

    public function lastDayOfWeekForStrtotime(): string
    {
        return match ($this->value()) {
            self::SUNDAY => 'saturday',
            self::SATURDAY => 'friday',
            default => 'sunday',
        };
    }
}
