<?php

declare(strict_types=1);

/**
 * This file contains class::MinPer100m
 * @package Runalyze\Activity\PaceUnit
 */

namespace Runalyze\Activity\PaceUnit;

/**
 * Pace unit: min/100m
 * @author Hannes Christiansen
 * @package Runalyze\Activity\PaceUnit
 */
class MinPer100m extends AbstractTimeUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function unit(): int
    {
        return \Runalyze\Parameter\Application\PaceUnit::MIN_PER_100M;
    }

    /**
     * @codeCoverageIgnore
     */
    public function appendix(): string
    {
        return '/100m';
    }

    /**
     * Factor to transform 's/km' to current unit
     *
     * This should only be used for time formats.
     * 's/km' * factorForUnit() = [time in seconds per distance]
     */
    public function factorForUnit(): float
    {
        return 0.1;
    }
}
