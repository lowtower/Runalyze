<?php

declare(strict_types=1);

namespace Runalyze\Profile\Weather\Source;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

class WeatherSourceProfile extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    /**
     * @var int
     */
    public const OPEN_WEATHER_MAP = 1;

    /**
     * @var int
     */
    public const DATABASE_CACHE = 2;

    /**
     * @var int
     */
    public const DARK_SKY = 3;

    // #TSC
    /**
     * @var int
     */
    public const METEOSTAT_NET = 4;
}
