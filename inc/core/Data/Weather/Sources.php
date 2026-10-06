<?php

declare(strict_types=1);

/**
 * This file contains class::Sources
 * @package Runalyze
 */

namespace Runalyze\Data\Weather;

use Runalyze\Common\Enum\AbstractEnum;

/**
 * Enum for weather sources and their internal ids
 *
 * @author Hannes Christiansen
 * @package Runalyze\Data\Weather
 */
final class Sources extends AbstractEnum
{
    public const int OPENWEATHERMAP = 1;

    public const int DBWEATHERCACHE = 2;

    public const int DARKSKY = 3;

    public const int METEOSTAT_NET = 4;

    /**
     * @param int $sourceId id from internal enum
     */
    public static function stringFor(int $sourceId): string
    {
        return match ($sourceId) {
            self::DARKSKY => '<a href="https://darksky.net/poweredby/" target="_blank">Powered by Dark Sky</a>',
            self::OPENWEATHERMAP => '<a href="http://openweathermap.org/" target="_blank">openweathermap.org</a>',
            self::METEOSTAT_NET => '<a href="https://meteostat.net/" target="_blank">meteostat.net</a>',
            self::DBWEATHERCACHE => __('internal database'),
            default => throw new \InvalidArgumentException('Invalid source id'),
        };
    }
}
