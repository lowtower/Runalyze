<?php

declare(strict_types=1);

namespace Runalyze\Profile\Weather;

use Runalyze\Common\Enum\AbstractEnum;

class WeatherConditionProfile extends AbstractEnum
{
    /**
     * @var int
     */
    public const UNKNOWN = 1;

    /**
     * @var int
     */
    public const SUNNY = 2;

    /**
     * @var int
     */
    public const FAIR = 3;

    /**
     * @var int
     */
    public const CLOUDY = 4;

    /**
     * @var int
     */
    public const CHANGEABLE = 5;

    /**
     * @var int
     */
    public const RAINY = 6;

    /**
     * @var int
     */
    public const SNOWING = 7;

    /**
     * @var int
     */
    public const HEAVYRAIN = 8;

    /**
     * @var int
     */
    public const FOGGY = 9;

    /**
     * @var int
     */
    public const THUNDERSTORM = 10;

    /**
     * @var int
     */
    public const WINDY = 11;
}
