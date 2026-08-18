<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * #TSC add new sport "ClimbingIndoor"
 * @codeCoverageIgnore
 */
class ClimbingIndoor extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::CLIMBING_INDOOR);
    }

    public function getIconClass(): string
    {
        return 'icons8-Climbing';
    }

    public function getName()
    {
        // #TSC: german text, so we need no translation
        return __('Climbing Indoor');
    }

    public function getCaloriesPerHour()
    {
        return 500;
    }

    public function getAverageHeartRate()
    {
        return 120;
    }

    public function hasDistances(): bool
    {
        return false;
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
        return PaceEnum::SECONDS_PER_KILOMETER;
    }

    public function usesShortDisplay(): bool
    {
        return false;
    }
}
