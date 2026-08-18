<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * #TSC add new sport "Bergsteigen"
 * @codeCoverageIgnore
 */
class Mountaineering extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::MOUNTAINEERING);
    }

    public function getIconClass()
    {
        return 'icons8-Mountaineering';
    }

    public function getName()
    {
        // #TSC: german text, so we need no translation
        return __('Bergsteigen');
    }

    public function getCaloriesPerHour()
    {
        return 540;
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
