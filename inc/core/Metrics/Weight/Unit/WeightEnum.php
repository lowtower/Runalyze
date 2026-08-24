<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Weight\Unit;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

class WeightEnum extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    /**
     * @var int
     */
    public const KILOGRAM = 0;

    /**
     * @var int
     */
    public const POUNDS = 1;

    /**
     * @var int
     */
    public const STONES = 2;
}
