<?php

declare(strict_types=1);

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * #TSC add new sport "HITT" and "Cardio"
 * @codeCoverageIgnore
 */
class HiitCardio extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::HIIT_CARDIO);
    }

    public function getIconClass(): string
    {
        return 'icons8-Pushups';
    }

    public function getName(): string
    {
        // #TSC: translation is available
        return __('HIIT Cardio');
    }

    public function getCaloriesPerHour(): int
    {
        return 700;
    }

    public function getAverageHeartRate(): int
    {
        return 135;
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
