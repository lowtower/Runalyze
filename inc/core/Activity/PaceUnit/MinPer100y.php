<?php

declare(strict_types=1);

/**
 * This file contains class::MinPer100y
 * @package Runalyze\Activity\PaceUnit
 */

namespace Runalyze\Activity\PaceUnit;

use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * Pace unit: min/100y
 * @author Hannes Christiansen
 * @package Runalyze\Activity\PaceUnit
 */
class MinPer100y extends AbstractTimeUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function unit(): int
    {
        return \Runalyze\Parameter\Application\PaceUnit::MIN_PER_100Y;
    }

    /**
     * @codeCoverageIgnore
     */
    public function appendix(): string
    {
        return '/100y';
    }

    /**
     * Factor to transform 's/km' to current unit
     *
     * This should only be used for time formats.
     * 's/km' * factorForUnit() = [time in seconds per distance]
     */
    public function factorForUnit(): float
    {
        return 100 / DistanceUnitSystem::YARD_MULTIPLIER;
    }
}
