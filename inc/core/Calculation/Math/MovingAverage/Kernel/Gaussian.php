<?php

declare(strict_types=1);

/**
 * This file contains class::Gaussian
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

/**
 * Gaussian kernel
 *
 * @see https://en.wikipedia.org/wiki/Kernel_(statistics)#Kernel_functions_in_common_use
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */
class Gaussian extends AbstractKernel
{
    #[\Override]
    protected float $DefaultWidth = 6.0;

    public function __construct(float $width)
    {
        $this->NormalizationFactor = 6.0 / sqrt(2.0 * M_PI);

        parent::__construct($width);
    }

    protected function atTransformed(float $u): float
    {
        return exp(-0.5 * $u * $u);
    }
}
