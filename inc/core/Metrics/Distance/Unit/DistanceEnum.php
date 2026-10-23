<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Distance\Unit;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

class DistanceEnum extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    public const int KILOMETER = 0;

    public const int MILES = 1;

    public const int METER = 2;

    public const int YARDS = 3;

    public const int CENTIMETER = 4;

    public const int FEET = 5;
}
