<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Weight\Unit;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

class WeightEnum extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    public const int KILOGRAM = 0;

    public const int POUNDS = 1;

    public const int STONES = 2;
}
