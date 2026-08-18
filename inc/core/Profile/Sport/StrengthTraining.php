<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * #TSC add new sport "Krafttraining"
 * @codeCoverageIgnore
 */
class StrengthTraining extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::STRENGTH_TRAINING);
    }

    public function getIconClass(): string
    {
        return 'icons8-Weightlift';
    }

    public function getName()
    {
        // #TSC: translation is available
        return __('Krafttraining');
    }

    public function getCaloriesPerHour()
    {
        return 260;
    }

    public function getAverageHeartRate()
    {
        return 105;
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
        return PaceEnum::KILOMETER_PER_HOUR;
    }

    public function usesShortDisplay(): bool
    {
        return false;
    }
}
