<?php

/**
 * This file contains class::Tricube
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

/**
 * Tricube kernel
 *
 * @see https://en.wikipedia.org/wiki/Kernel_(statistics)#Kernel_functions_in_common_use
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */
class Tricube extends AbstractKernel
{
    /**
     * @param float $width
     */
    public function __construct($width)
    {
        $this->NormalizationFactor = 140 / 81;

        parent::__construct($width);
    }

    /**
     * @param float $u
     */
    protected function atTransformed($u): float
    {
        if ($u >= -1.0 && $u <= 1.0) {
            return pow(1 - abs($u * $u * $u), 3);
        }

        return 0.0;
    }
}
