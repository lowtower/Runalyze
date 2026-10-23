<?php

declare(strict_types=1);

/**
 * This file contains class::Logistic
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

/**
 * Logistic kernel
 *
 * @see https://en.wikipedia.org/wiki/Kernel_(statistics)#Kernel_functions_in_common_use
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */
class Logistic extends AbstractKernel
{
    #[\Override]
    protected float $DefaultWidth = 10.0;

    #[\Override]
    protected float $NormalizationFactor = 10.0;

    protected function atTransformed(float $u): float
    {
        return 1.0 / (exp($u) + 2.0 + exp(-$u));
    }
}
