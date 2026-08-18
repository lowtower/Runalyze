<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * Fallback for user-defined sport types
 *
 * @codeCoverageIgnore
 */
class Generic extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::GENERIC);
    }

    public function getIconClass(): string
    {
        return 'icons8-Sports-Mode';
    }

    public function getName()
    {
        return __('Generic');
    }

    public function getCaloriesPerHour(): int
    {
        return 500;
    }

    public function getAverageHeartRate(): int
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
        return PaceEnum::KILOMETER_PER_HOUR;
    }

    public function usesShortDisplay(): bool
    {
        return false;
    }
}
