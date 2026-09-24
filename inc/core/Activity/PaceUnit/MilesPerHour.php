<?php

declare(strict_types=1);

/**
 * This file contains class::MilesPerHour
 * @package Runalyze\Activity\PaceUnit
 */

namespace Runalyze\Activity\PaceUnit;

use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * Pace unit: mph
 * @author Hannes Christiansen
 * @package Runalyze\Activity\PaceUnit
 */
class MilesPerHour extends AbstractDecimalUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function unit(): int
    {
        return \Runalyze\Parameter\Application\PaceUnit::MILES_PER_H;
    }

    /**
     * @codeCoverageIgnore
     */
    public function appendix(): string
    {
        return '&nbsp;mph';
    }

    /**
     * Dividend to transform 's/km' to current unit
     *
     * This should only be used for decimal formats.
     * dividendForUnit() / 's/km' = [decimal pace value]
     */
    public function dividendForUnit(): float
    {
        return 3600 * DistanceUnitSystem::MILE_MULTIPLIER;
    }
}
