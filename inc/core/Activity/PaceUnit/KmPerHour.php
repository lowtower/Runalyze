<?php

declare(strict_types=1);

/**
 * This file contains class::KmPerHour
 * @package Runalyze\Activity\PaceUnit
 */

namespace Runalyze\Activity\PaceUnit;

use Runalyze\Parameter\Application\PaceUnit;

/**
 * Pace unit: km/h
 * @author Hannes Christiansen
 * @package Runalyze\Activity\PaceUnit
 */
class KmPerHour extends AbstractDecimalUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function unit(): int
    {
        return PaceUnit::KM_PER_H;
    }

    /**
     * @codeCoverageIgnore
     */
    public function appendix(): string
    {
        return '&nbsp;km/h';
    }

    /**
     * Dividend to transform 's/km' to current unit
     *
     * This should only be used for decimal formats.
     * dividendForUnit() / 's/km' = [decimal pace value]
     */
    public function dividendForUnit(): float
    {
        return 3600.0;
    }
}
