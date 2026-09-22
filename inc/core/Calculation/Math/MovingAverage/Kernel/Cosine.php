<?php

declare(strict_types=1);

/**
 * This file contains class::Cosine
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

/**
 * Cosine kernel
 *
 * @see https://en.wikipedia.org/wiki/Kernel_(statistics)#Kernel_functions_in_common_use
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */
class Cosine extends AbstractKernel
{
    public function __construct(float $width)
    {
        $this->NormalizationFactor = M_PI / 2.0;

        parent::__construct($width);
    }

    protected function atTransformed(float $u): float
    {
        if ($u >= -1.0 && $u <= 1.0) {
            return cos($u * M_PI / 2.0);
        }

        return 0.0;
    }
}
