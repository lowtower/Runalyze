<?php

declare(strict_types=1);

/**
 * This file contains class::Quartic
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

/**
 * Quartic kernel
 *
 * @see https://en.wikipedia.org/wiki/Kernel_(statistics)#Kernel_functions_in_common_use
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */
class Quartic extends AbstractKernel
{
    public function __construct(float $width)
    {
        $this->NormalizationFactor = 30 / 16;

        parent::__construct($width);
    }

    protected function atTransformed(float $u): float
    {
        if ($u >= -1.0 && $u <= 1.0) {
            return (1 - $u * $u) ** 2;
        }

        return 0.0;
    }
}
