<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * #TSC add new sport "Langlauf"
 * @codeCoverageIgnore
 */
class CrossCountrySkiing extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::CROSS_COUNTRY_SKIING);
    }

    public function getIconClass(): string
    {
        return 'icons8-CrossCountrySkiing';
    }

    public function getName()
    {
        // #TSC: german text, so we need no translation
        return __('Langlauf');
    }

    public function getCaloriesPerHour()
    {
        return 500;
    }

    public function getAverageHeartRate()
    {
        return 115;
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
