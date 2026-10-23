<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;
use Runalyze\Util\InterfaceChoosable;

class SportProfile extends AbstractEnum implements InterfaceChoosable
{
    use AbstractEnumFactoryTrait;

    public const int GENERIC = 0;

    public const int RUNNING = 1;

    public const int CYCLING = 2;

    public const int SWIMMING = 3;

    public const int ROWING = 4;

    public const int HIKING = 5;

    public const int MOUNTAINEERING = 6;

    public const int SNOW_SHOEING = 7;

    public const int CROSS_COUNTRY_SKIING = 8;

    public const int BOULDERING = 9;

    public const int CLIMBING_INDOOR = 10;

    public const int STRENGTH_TRAINING = 11;

    public const int E_MTB_MOUNTAIN = 12;

    public const int HIIT_CARDIO = 13;

    public static function getChoices(): array
    {
        $choices = [];

        foreach (self::getEnum() as $enum) {
            $choices[self::get($enum)->getName()] = $enum;
        }

        return $choices;
    }
}
