<?php

declare(strict_types=1);

namespace Runalyze\Profile\Weather;

use Runalyze\Common\Enum\AbstractEnum;

class WeatherConditionProfile extends AbstractEnum
{
    public const int UNKNOWN = 1;

    public const int SUNNY = 2;

    public const int FAIR = 3;

    public const int CLOUDY = 4;

    public const int CHANGEABLE = 5;

    public const int RAINY = 6;

    public const int SNOWING = 7;

    public const int HEAVYRAIN = 8;

    public const int FOGGY = 9;

    public const int THUNDERSTORM = 10;

    public const int WINDY = 11;
}
