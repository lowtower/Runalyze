<?php

declare(strict_types=1);

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

    public function getIconClass(): string
    {
        return 'icons8-Swimming';
    }

    public function getName(): string
    {
        return __('Swimming');
    }

    public function getCaloriesPerHour(): int
    {
        return 743;
    }

    public function getAverageHeartRate(): int
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
