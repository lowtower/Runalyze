<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * E-MTB Mountain.
 * #TSC
 *
 * @codeCoverageIgnore
 */
class EMtbMountain extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::E_MTB_MOUNTAIN);
    }

    public function getIconClass(): string
    {
        return 'icons8-Mountain-Biking';
    }

    public function getName()
    {
        return __('E-MTB Berg');
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
