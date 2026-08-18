<?php

declare(strict_types=1);

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * @codeCoverageIgnore
 */
class Rowing extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::ROWING);
    }

    public function getIconClass(): string
    {
        return 'icons8-Rowing';
    }

    public function getName(): string
    {
        return __('Rowing');
    }

    public function getCaloriesPerHour(): int
    {
        return 510;
    }

    public function getAverageHeartRate(): int
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
        return false;
    }

    public function getPaceUnitEnum(): int
    {
        return PaceEnum::SECONDS_PER_500M;
    }

    public function usesShortDisplay(): bool
    {
        return false;
    }
}
