<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Temperature\Unit;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

class TemperatureEnum extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    public const int CELSIUS = 0;

    public const int FAHRENHEIT = 1;
}
