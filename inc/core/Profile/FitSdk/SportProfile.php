<?php

declare(strict_types=1);

namespace Runalyze\Profile\FitSdk;

use Runalyze\Common\Enum\AbstractEnum;

/**
 * @codeCoverageIgnore
 */
class SportProfile extends AbstractEnum
{
    public const int GENERIC = 0;

    public const int RUNNING = 1;

    public const int CYCLING = 2;

    public const int TRANSITION = 3;

    public const int FITNESS_EQUIPMENT = 4;

    public const int SWIMMING = 5;

    public const int BASKETBALL = 6;

    public const int SOCCER = 7;

    public const int TENNIS = 8;

    public const int AMERICAN_FOOTBALL = 9;

    public const int TRAINING = 10;

    public const int WALKING = 11;

    public const int CROSS_COUNTRY_SKIING = 12;

    public const int ALPINE_SKIING = 13;

    public const int SNOWBOARDING = 14;

    public const int ROWING = 15;

    public const int MOUNTAINEERING = 16;

    public const int HIKING = 17;

    public const int MULTISPORT = 18;

    public const int PADDLING = 19;

    public const int FLYING = 20;

    public const int E_BIKING = 21;

    public const int MOTORCYCLING = 22;

    public const int BOATING = 23;

    public const int DRIVING = 24;

    public const int GOLF = 25;

    public const int HANG_GLIDING = 26;

    public const int HORSEBACK_RIDING = 27;

    public const int HUNTING = 28;

    public const int FISHING = 29;

    public const int INLINE_SKATING = 30;

    public const int ROCK_CLIMBING = 31;

    public const int SAILING = 32;

    public const int ICE_SKATING = 33;

    public const int SKY_DIVING = 34;

    public const int SNOWSHOEING = 35;

    public const int SNOWMOBILING = 36;

    public const int STAND_UP_PADDLEBOARDING = 37;

    public const int SURFING = 38;

    public const int WAKEBOARDING = 39;

    public const int WATER_SKIING = 40;

    public const int KAYAKING = 41;

    public const int RAFTING = 42;

    public const int WINDSURFING = 43;

    public const int KITESURFING = 44;

    public const int TACTICAL = 45;

    public const int JUMPMASTER = 46;

    public const int BOXING = 37;

    public const int HIIT = 62;

    // #TSC new to use sub-sport: identified by sport*1000 and the subsport
    public const int CLIMBING_INDOOR = 31068;

    // sub of ROCK_CLIMBING=31
    public const int BOULDERING = 31069;

    // sub of ROCK_CLIMBING=31
    public const int STRENGTH_TRAINING = 10020;

    // sub of TRAINING=10
    public const int E_MTB_MOUNTAIN = 2047;

    // sub of cycling=2 e_bike_mountain=47
    public const int CARDIO = 10026;

    // sub of training=10 cardio_training=26
    public const int ALL = 254;
}
