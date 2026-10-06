<?php

declare(strict_types=1);

/**
 * This file contains class::SummaryMode
 * @package Runalyze
 */

namespace Runalyze\Dataset;

use Runalyze\Common\Enum\AbstractEnum;

/**
 * Enum for summary modes for dataset
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset
 */
final class SummaryMode extends AbstractEnum
{
    /**
     * NO: This value can't be summarized.
     */
    public const int NO = 0;

    /**
     * AVG: This value will be summarized by its average.
     */
    public const int AVG = 1;

    /**
     * SUM: This value will be summarized by its sum.
     */
    public const int SUM = 2;

    /**
     * MAX: This value will be summarized by its maximum.
     */
    public const int MAX = 3;

    /**
     * MIN: This value will be summarized by its maximum.
     */
    public const int MIN = 4;

    /**
     * AVG_WITHOUT_NULL: This value will be summarized by its average ignoring nulls.
     */
    public const int AVG_WITHOUT_NULL = 5;

    /**
     * VO2MAX: take only the average of activities with `use_vo2max` and respect elevation correction
     */
    public const int VO2MAX = 6;

    /**
     * AVG_WITH_ZERO: This value will not ignore zeros.
     */
    public const int AVG_WITH_ZERO = 7;

    /**
     * @param int $mode int from internal enum
     * @param string $key key of database column
     * @return string query part to select column
     */
    public static function query(int $mode, string $key): string
    {
        return match ($mode) {
            self::AVG => self::queryForAvg($key),
            self::SUM => self::queryForSum($key),
            self::MAX => self::queryForMax($key),
            self::MIN => self::queryForMin($key),
            self::AVG_WITHOUT_NULL => self::queryForAvgWithoutNull($key),
            self::VO2MAX => self::queryForVO2max($key),
            self::AVG_WITH_ZERO => self::queryForAvgWithZero($key),
            default => '',
        };
    }

    public static function queryForAvg(string|array $key): string
    {
        if (is_array($key)) {
            return implode(', ', array_map(self::queryForAvg(...), $key));
        }

        return 'SUM(`s`*`' . $key . '`*(`' . $key . '` != 0))/SUM(`s`*(`' . $key . '` != 0)) as `' . $key . '`';
    }

    private static function queryForSum(string $key): string
    {
        return 'SUM(`' . $key . '`) as `' . $key . '`';
    }

    private static function queryForMax(string $key): string
    {
        return 'MAX(`' . $key . '`) as `' . $key . '`';
    }

    private static function queryForMin(string $key): string
    {
        return 'MIN(`' . $key . '`) as `' . $key . '`';
    }

    private static function queryForAvgWithoutNull(string $key): string
    {
        return 'AVG(NULLIF(`' . $key . '`,0)) as `' . $key . '`';
    }

    public static function queryForAvgWithZero(string|array $key): string
    {
        if (is_array($key)) {
            return implode(', ', array_map(self::queryForAvgWithZero(...), $key));
        }

        return 'SUM(`s`*`' . $key . '`)/SUM(`s`) as `' . $key . '`';
    }

    private static function queryForVO2max(string $key): string
    {
        $Sum = \Runalyze\Configuration::VO2max()->useElevationCorrection() ? 'IF(`vo2max_with_elevation`>0,`vo2max_with_elevation`,`vo2max`)*`s`' : '`vo2max`*`s`';

        return 'SUM(IF(`use_vo2max`=1 AND `vo2max`>0,' . $Sum . ',0))/SUM(IF(`use_vo2max`=1 AND `vo2max`>0,`s`,0)) as `' . $key . '`';
    }
}
