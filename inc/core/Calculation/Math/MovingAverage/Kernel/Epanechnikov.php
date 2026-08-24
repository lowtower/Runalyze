<?php

declare(strict_types=1);

/**
 * This file contains class::Epanechnikov
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

/**
 * Epanechnikov kernel
 *
 * @see https://en.wikipedia.org/wiki/Kernel_(statistics)#Kernel_functions_in_common_use
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */
class Epanechnikov extends AbstractKernel
{
    /**
     * @var float
     */
    protected $NormalizationFactor = 1.5;

    /**
     * @param float $u
     * @return float
     */
    protected function atTransformed($u)
    {
        if ($u >= -1.0 && $u <= 1.0) {
            return 1 - $u * $u;
        }

        return 0.0;
    }
}
