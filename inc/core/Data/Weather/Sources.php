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
    /**
     * @var int
     */
    public const OPENWEATHERMAP = 1;

    /**
     * @var int
     */
    public const DBWEATHERCACHE = 2;

    /**
     * @var int
     */
    public const DARKSKY = 3;

    /**
     * @var int
     */
    public const METEOSTAT_NET = 4;

    /**
     * @param int $sourceId id from internal enum
     */
    public static function stringFor($sourceId): string
    {
        switch ($sourceId) {
            case self::DARKSKY:
                return '<a href="https://darksky.net/poweredby/" target="_blank">Powered by Dark Sky</a>';
            case self::OPENWEATHERMAP:
                return '<a href="http://openweathermap.org/" target="_blank">openweathermap.org</a>';
            case self::METEOSTAT_NET:
                return '<a href="https://meteostat.net/" target="_blank">meteostat.net</a>';
            case self::DBWEATHERCACHE:
                return __('internal database');
            default:
                throw new \InvalidArgumentException('Invalid source id');
        }
    }
}
