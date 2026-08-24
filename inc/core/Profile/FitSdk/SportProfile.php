<?php

declare(strict_types=1);

namespace Runalyze\Profile\FitSdk;

use Runalyze\Common\Enum\AbstractEnum;

/**
 * @codeCoverageIgnore
 */
class SportProfile extends AbstractEnum
{
    public const GENERIC = 0;

    public const RUNNING = 1;

    public const CYCLING = 2;

    public const TRANSITION = 3;

    public const FITNESS_EQUIPMENT = 4;

    public const SWIMMING = 5;

    public const BASKETBALL = 6;

    public const SOCCER = 7;

    public const TENNIS = 8;

    public const AMERICAN_FOOTBALL = 9;

    public const TRAINING = 10;

    public const WALKING = 11;

    public const CROSS_COUNTRY_SKIING = 12;

    public const ALPINE_SKIING = 13;

    public const SNOWBOARDING = 14;

    public const ROWING = 15;

    public const MOUNTAINEERING = 16;

    public const HIKING = 17;

    public const MULTISPORT = 18;

    public const PADDLING = 19;

    public const FLYING = 20;

    public const E_BIKING = 21;

    public const MOTORCYCLING = 22;

    public const BOATING = 23;

    public const DRIVING = 24;

    public const GOLF = 25;

    public const HANG_GLIDING = 26;

    public const HORSEBACK_RIDING = 27;

    public const HUNTING = 28;

    public const FISHING = 29;

    public const INLINE_SKATING = 30;

    public const ROCK_CLIMBING = 31;

    public const SAILING = 32;

    public const ICE_SKATING = 33;

    public const SKY_DIVING = 34;

    public const SNOWSHOEING = 35;

    public const SNOWMOBILING = 36;

    public const STAND_UP_PADDLEBOARDING = 37;

    public const SURFING = 38;

    public const WAKEBOARDING = 39;

    public const WATER_SKIING = 40;

    public const KAYAKING = 41;

    public const RAFTING = 42;

    public const WINDSURFING = 43;

    public const KITESURFING = 44;

    public const TACTICAL = 45;

    public const JUMPMASTER = 46;

    public const BOXING = 37;

    public const HIIT = 62;

    // #TSC new to use sub-sport: identified by sport*1000 and the subsport
    public const CLIMBING_INDOOR = 31068;

    // sub of ROCK_CLIMBING=31
    public const BOULDERING = 31069;

    // sub of ROCK_CLIMBING=31
    public const STRENGTH_TRAINING = 10020;

    // sub of TRAINING=10
    public const E_MTB_MOUNTAIN = 2047;

    // sub of cycling=2 e_bike_mountain=47
    public const CARDIO = 10026;

    // sub of training=10 cardio_training=26
    public const ALL = 254;
}
