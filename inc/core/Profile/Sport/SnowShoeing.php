<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * #TSC add new sport "Schneeschuh"
 * @codeCoverageIgnore
 */
class SnowShoeing extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::SNOW_SHOEING);
    }

    public function getIconClass()
    {
        return 'icons8-SnowShoeing';
    }

    public function getName()
    {
        // #TSC: german text, so we need no translation
        return __('Schneeschuh');
    }

    public function getCaloriesPerHour()
    {
        return 450;
    }

    public function getAverageHeartRate()
    {
        return 120;
    }

    public function hasDistances(): bool
    {
        return true;
    }

    public function hasPower(): bool
    {
        return false;
    }

    public function isOutside(): bool
    {
        return true;
    }

    public function getPaceUnitEnum(): int
    {
        return PaceEnum::KILOMETER_PER_HOUR;
    }

    public function usesShortDisplay(): bool
    {
        return false;
    }
}
