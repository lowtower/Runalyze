<?php

declare(strict_types=1);

/**
 * This file contains class::NoSmoothing
 * @package Runalyze\Calculation\Elevation\Strategy
 */

namespace Runalyze\Calculation\Elevation\Strategy;

/**
 * Smoothing strategy: no smoothing
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Elevation\Strategy
 */
class NoSmoothing extends AbstractStrategy
{
    /**
     * Smooth data
     */
    public function runSmoothing(): void
    {
        $this->SmoothedData = $this->ElevationData;
        $this->SmoothingIndices = array_keys($this->SmoothedData);
    }
}
