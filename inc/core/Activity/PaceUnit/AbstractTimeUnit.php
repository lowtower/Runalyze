<?php

declare(strict_types=1);

/**
 * This file contains class::AbstractTimeUnit
 * @package Runalyze\Activity\PaceUnit
 */

namespace Runalyze\Activity\PaceUnit;

use Runalyze\Activity\Duration;

/**
 * Abstract time pace unit
 * @author Hannes Christiansen
 * @package Runalyze\Activity\PaceUnit
 */
abstract class AbstractTimeUnit extends AbstractUnit
{
    /**
     * Pace is displayed as time per distance
     */
    final public function isTimeFormat(): bool
    {
        return true;
    }

    /**
     * Dividend to transform 's/km' to current unit
     *
     * This should only be used for decimal formats.
     * dividendForUnit() / 's/km' = [decimal pace value]
     *
     * @throws \RuntimeException
     */
    public function dividendForUnit(): float
    {
        throw new \RuntimeException('factorForUnit() must not be used for units in decimal format');
    }

    /**
     * Get raw value
     *
     * This returns for example a float for decimal units and an integer as 's/km'
     * for time units.
     */
    public function rawValue(float $secondsPerKm): float
    {
        return $secondsPerKm * $this->factorForUnit();
    }

    /**
     * Format pace
     */
    public function format(float $secondsPerKm): string
    {
        if ($secondsPerKm === 0.0) {
            return '-:--';
        }

        return Duration::format(round($secondsPerKm * $this->factorForUnit()));
    }

    /**
     * Compare two paces
     *
     * This value is positive if $firstPaceInSecondsPerKm is larger than $secondPaceInSecondsPerKm
     *
     * @return float [s/km]
     */
    public function compare(float $firstPaceInSecondsPerKm, float $secondPaceInSecondsPerKm): float
    {
        return $secondPaceInSecondsPerKm - $firstPaceInSecondsPerKm;
    }
}
