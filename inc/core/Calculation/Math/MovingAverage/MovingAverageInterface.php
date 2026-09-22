<?php

declare(strict_types=1);

/**
 * This file contains class::MovingAverageInterface
 * @package Runalyze\Calculation\Math\MovingAverage
 */

namespace Runalyze\Calculation\Math\MovingAverage;

/**
 * Interface for moving averages
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage
 */
interface MovingAverageInterface
{
    public function movingAverage(): array;

    /**
     * @param int $index
     */
    public function at($index): float;
}
