<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Energy\Unit;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

class EnergyEnum extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    /**
     * @var int
     */
    public const KILOCALORIES = 0;

    /**
     * @var int
     */
    public const KILOJOULES = 1;
}
