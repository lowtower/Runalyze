<?php

declare(strict_types=1);

/**
 * This file contains class::Triangular
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

/**
 * Triangular kernel
 *
 * @see https://en.wikipedia.org/wiki/Kernel_(statistics)#Kernel_functions_in_common_use
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */
class Triangular extends AbstractKernel
{
    #[\Override]
    protected float $NormalizationFactor = 2.0;

    protected function atTransformed(float $u): float
    {
        if ($u >= -1.0 && $u <= 1.0) {
            return 1.0 - abs($u);
        }

        return 0.0;
    }
}
