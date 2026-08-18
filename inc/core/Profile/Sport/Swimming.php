<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * @codeCoverageIgnore
 */
class Swimming extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::SWIMMING);
    }

    public function getIconClass()
    {
        return 'icons8-Swimming';
    }

    public function getName()
    {
        return __('Swimming');
    }

    public function getCaloriesPerHour()
    {
        return 743;
    }

    public function getAverageHeartRate()
    {
        return 130;
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
        return false;
    }

    public function getPaceUnitEnum(): int
    {
        return PaceEnum::SECONDS_PER_100M;
    }

    public function usesShortDisplay(): bool
    {
        return false;
    }
}
