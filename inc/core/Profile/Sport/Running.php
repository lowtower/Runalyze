<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * @codeCoverageIgnore
 */
class Running extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::RUNNING);
    }

    public function getIconClass(): string
    {
        return 'icons8-Running';
    }

    public function getName()
    {
        return __('Running');
    }

    public function getCaloriesPerHour(): int
    {
        return 880;
    }

    public function getAverageHeartRate(): int
    {
        return 140;
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
        return PaceEnum::SECONDS_PER_KILOMETER;
    }

    public function usesShortDisplay(): bool
    {
        return false;
    }
}
