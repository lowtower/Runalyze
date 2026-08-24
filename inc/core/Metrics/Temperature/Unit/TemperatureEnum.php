<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Temperature\Unit;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

class TemperatureEnum extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    /**
     * @var int
     */
    public const CELSIUS = 0;

    /**
     * @var int
     */
    public const FAHRENHEIT = 1;
}
