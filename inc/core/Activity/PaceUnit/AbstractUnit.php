<?php

declare(strict_types=1);

/**
 * This file contains class::AbstractUnit
 * @package Runalyze\Activity\PaceUnit
 */

namespace Runalyze\Activity\PaceUnit;

/**
 * Abstract pace unit
 * @author Hannes Christiansen
 * @package Runalyze\Activity\PaceUnit
 */
abstract class AbstractUnit
{
    /**
     * @codeCoverageIgnore
     */
    abstract public function unit(): int;

    /**
     * @codeCoverageIgnore
     */
    abstract public function appendix(): string;

    /**
     * Pace is displayed as time per distance
     */
    abstract public function isTimeFormat(): bool;

    /**
     * Pace is displayed in decimal format
     */
    final public function isDecimalFormat(): bool
    {
        return ! $this->isTimeFormat();
    }

    /**
     * Factor to transform 's/km' to current unit
     *
     * This should only be used for time formats.
     * 's/km' * factorForUnit() = [time in seconds per distance]
     *
     * @throws \RuntimeException
     */
    abstract public function factorForUnit(): float;

    /**
     * Dividend to transform 's/km' to current unit
     *
     * This should only be used for decimal formats.
     * dividendForUnit() / 's/km' = [decimal pace value]
     *
     * @throws \RuntimeException
     */
    abstract public function dividendForUnit(): float;

    /**
     * Get raw value
     *
     * This returns for example a float for decimal units and an integer as 's/km'
     * for time units.
     *
     * @return mixed
     */
    abstract public function rawValue(int $secondsPerKm);

    /**
     * Format pace
     */
    abstract public function format(int $secondsPerKm): string;

    /**
     * Compare two paces
     *
     * This value is positive if $firstPaceInSecondsPerKm is larger than $secondPaceInSecondsPerKm
     *
     * @param int $firstPaceInSecondsPerKm
     * @param int $secondPaceInSecondsPerKm
     * @return float [s/km]
     */
    abstract public function compare($firstPaceInSecondsPerKm, $secondPaceInSecondsPerKm);
}
