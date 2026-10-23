<?php

declare(strict_types=1);

namespace Runalyze\Profile\Weather\Source;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

class WeatherSourceProfile extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    public const int OPEN_WEATHER_MAP = 1;

    public const int DATABASE_CACHE = 2;

    public const int DARK_SKY = 3;

    // #TSC
    public const int METEOSTAT_NET = 4;
}
