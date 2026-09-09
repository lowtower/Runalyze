<?php

declare(strict_types=1);

/**
 * This file contains class::RPE
 * @package Runalyze\Data
 */

namespace Runalyze\Data;

/**
 * RPE - Perceived Exertion Scale
 *
 * @author Michael Pohl
 * @package Runalyze\Data
 */
class RPE
{
    /**
     * @return array<int, string>
     */
    public static function completeList(): array
    {
        // #TSC change from old 6-15 Borg to CR10 Borg
        return [
            1 => '1 - ' . __('Very light'),
            2 => '2 - ' . __('Light'),
            3 => '3 - Moderat',
            4 => '4 - ' . __('Somewhat hard'),
            5 => '5 - ' . __('Hard'),
            6 => '6 - ' . __('Hard'),
            7 => '7 - ' . __('Very Hard'),
            8 => '8 - ' . __('Very Hard'),
            9 => '9 - ' . __('Extremely hard'),
            10 => '10 - ' . __('Maximal exertion'),
        ];
    }

    public static function validRPE(int $value): bool
    {
        return $value >= 1 && $value <= 10;
    }

    public static function getString(int $value)
    {
        if (self::validRPE($value)) {
            return self::completeList()[$value];
        }
    }
}
