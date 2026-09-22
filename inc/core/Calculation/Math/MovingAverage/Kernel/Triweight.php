<?php

declare(strict_types=1);

/**
 * This file contains class::Triweight
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

/**
 * Triweight kernel
 *
 * @see https://en.wikipedia.org/wiki/Kernel_(statistics)#Kernel_functions_in_common_use
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */
class Triweight extends AbstractKernel
{
    public function __construct(float $width)
    {
        $this->NormalizationFactor = 70 / 32;

        parent::__construct($width);
    }

    protected function atTransformed(float $u): float
    {
        if ($u >= -1.0 && $u <= 1.0) {
            return (1 - $u * $u) ** 3;
        }

        return 0.0;
    }
}
