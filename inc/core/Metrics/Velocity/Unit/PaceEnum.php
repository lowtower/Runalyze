<?php

namespace Runalyze\Metrics\Velocity\Unit;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;
use Runalyze\Util\InterfaceChoosable;

class PaceEnum extends AbstractEnum implements InterfaceChoosable
{
    use AbstractEnumFactoryTrait;

    public const int SECONDS_PER_KILOMETER = 0;

    public const int SECONDS_PER_MILE = 1;

    public const int SECONDS_PER_500M = 2;

    public const int SECONDS_PER_500Y = 3;

    public const int SECONDS_PER_100M = 4;

    public const int SECONDS_PER_100Y = 5;

    public const int KILOMETER_PER_HOUR = 6;

    public const int MILES_PER_HOUR = 7;

    public const int METER_PER_SECOND = 8;

    /**
     * @return mixed[]
     */
    public static function getChoices(): array
    {
        $choices = [];

        foreach (self::getEnum() as $enum) {
            $choices[self::get($enum)->getUnit()] = $enum;
        }

        return $choices;
    }
}
