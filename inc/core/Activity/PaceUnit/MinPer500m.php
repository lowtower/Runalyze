<?php

declare(strict_types=1);

/**
 * This file contains class::MinPer500m
 * @package Runalyze\Activity\PaceUnit
 */

namespace Runalyze\Activity\PaceUnit;

/**
 * Pace unit: min/500m
 * @author Hannes Christiansen
 * @package Runalyze\Activity\PaceUnit
 */
class MinPer500m extends AbstractTimeUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function unit(): int
    {
        return \Runalyze\Parameter\Application\PaceUnit::MIN_PER_500M;
    }

    /**
     * @codeCoverageIgnore
     */
    public function appendix(): string
    {
        return '/500m';
    }

    /**
     * Factor to transform 's/km' to current unit
     *
     * This should only be used for time formats.
     * 's/km' * factorForUnit() = [time in seconds per distance]
     *
     * @throws \RuntimeException
     */
    public function factorForUnit(): float
    {
        return 0.5;
    }
}
