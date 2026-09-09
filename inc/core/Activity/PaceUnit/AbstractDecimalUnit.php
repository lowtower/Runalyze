<?php

/**
 * This file contains class::AbstractDecimalUnit
 * @package Runalyze\Activity\PaceUnit
 */

namespace Runalyze\Activity\PaceUnit;

/**
 * Abstract decimal pace unit
 * @author Hannes Christiansen
 * @package Runalyze\Activity\PaceUnit
 */
abstract class AbstractDecimalUnit extends AbstractUnit
{
    /**
     * @var string
     */
    public static $DecimalSeparator = ',';

    /**
     * @var int
     */
    public static $Decimals = 1;

    /**
     * Pace is displayed as time per distance
     */
    final public function isTimeFormat(): bool
    {
        return false;
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
        throw new \RuntimeException('factorForUnit() must not be used for units in decimal format');
    }

    /**
     * Get raw value
     *
     * This returns for example a float for decimal units and an integer as 's/km'
     * for time units.
     *
     * @return mixed
     */
    public function rawValue(int $secondsPerKm)
    {
        if ($secondsPerKm === 0) {
            return 0;
        }

        return $this->dividendForUnit() / $secondsPerKm;
    }

    /**
     * Format pace
     */
    public function format(int $secondsPerKm): string
    {
        if ($secondsPerKm === 0) {
            return '0' . self::$DecimalSeparator . '0';
        }

        return number_format($this->rawValue($secondsPerKm), self::$Decimals, self::$DecimalSeparator, '');
    }

    /**
     * Compare two paces
     *
     * This value is positive if $firstPaceInSecondsPerKm is larger than $secondPaceInSecondsPerKm
     *
     * @param int $firstPaceInSecondsPerKm
     * @param int $secondPaceInSecondsPerKm
     * @return float [s/km]
     */
    public function compare($firstPaceInSecondsPerKm, $secondPaceInSecondsPerKm)
    {
        if ($firstPaceInSecondsPerKm == $secondPaceInSecondsPerKm) {
            return 0;
        }

        return $firstPaceInSecondsPerKm * $secondPaceInSecondsPerKm / ($secondPaceInSecondsPerKm - $firstPaceInSecondsPerKm);
    }
}
