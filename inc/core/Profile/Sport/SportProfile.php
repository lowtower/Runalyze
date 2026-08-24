<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;
use Runalyze\Util\InterfaceChoosable;

class SportProfile extends AbstractEnum implements InterfaceChoosable
{
    use AbstractEnumFactoryTrait;

    /**
     * @var int
     */
    public const GENERIC = 0;

    /**
     * @var int
     */
    public const RUNNING = 1;

    /**
     * @var int
     */
    public const CYCLING = 2;

    /**
     * @var int
     */
    public const SWIMMING = 3;

    /**
     * @var int
     */
    public const ROWING = 4;

    /**
     * @var int
     */
    public const HIKING = 5;

    /**
     * @var int
     */
    public const MOUNTAINEERING = 6;

    /**
     * @var int
     */
    public const SNOW_SHOEING = 7;

    /**
     * @var int
     */
    public const CROSS_COUNTRY_SKIING = 8;

    /**
     * @var int
     */
    public const BOULDERING = 9;

    /**
     * @var int
     */
    public const CLIMBING_INDOOR = 10;

    /**
     * @var int
     */
    public const STRENGTH_TRAINING = 11;

    /**
     * @var int
     */
    public const E_MTB_MOUNTAIN = 12;

    /**
     * @var int
     */
    public const HIIT_CARDIO = 13;

    public static function getChoices(): array
    {
        $choices = [];

        foreach (self::getEnum() as $enum) {
            $choices[self::get($enum)->getName()] = $enum;
        }

        return $choices;
    }
}
