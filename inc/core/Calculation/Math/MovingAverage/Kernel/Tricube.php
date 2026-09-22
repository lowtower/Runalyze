<?php

declare(strict_types=1);

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
    public function __construct(float $width)
    {
        $this->NormalizationFactor = 140 / 81;

        parent::__construct($width);
    }

    protected function atTransformed(float $u): float
    {
        if ($u >= -1.0 && $u <= 1.0) {
            return (1 - abs($u * $u * $u)) ** 3;
        }

        return 0.0;
    }
}
