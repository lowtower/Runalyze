<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Distance\Unit;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

class DistanceEnum extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    /**
     * @var int
     */
    public const KILOMETER = 0;

    /**
     * @var int
     */
    public const MILES = 1;

    /**
     * @var int
     */
    public const METER = 2;

    /**
     * @var int
     */
    public const YARDS = 3;

    /**
     * @var int
     */
    public const CENTIMETER = 4;

    /**
     * @var int
     */
    public const FEET = 5;
}
