<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * @codeCoverageIgnore
 */
class Cycling extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::CYCLING);
    }

    public function getIconClass(): string
    {
        return 'icons8-Regular-Biking';
    }

    public function getName()
    {
        return __('Cycling');
    }

    public function getCaloriesPerHour()
    {
        return 770;
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
        return true;
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
