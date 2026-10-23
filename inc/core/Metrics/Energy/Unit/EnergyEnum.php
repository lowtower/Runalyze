<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Energy\Unit;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

class EnergyEnum extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    public const int KILOCALORIES = 0;

    public const int KILOJOULES = 1;
}
