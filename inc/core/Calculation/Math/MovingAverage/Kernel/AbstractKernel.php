<?php

declare(strict_types=1);

/**
 * This file contains class::AbstractKernel
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

/**
 * Abstract class for moving average kernels
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */
abstract class AbstractKernel
{
    private readonly float $TransformationFactor;

    protected float $Width;

    protected float $DefaultWidth = 2.0;

    protected float $NormalizationFactor = 1.0;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(float $width)
    {
        if ($width <= 0.0) {
            throw new \InvalidArgumentException('Kernel width must be a positive float.');
        }

        $this->Width = $width;
        $this->TransformationFactor = $this->DefaultWidth / $this->Width;
    }

    final public function width(): float
    {
        return $this->Width;
    }

    /**
     * @param float $difference difference to X_0
     * @param bool $normalized flag if values should be normalized
     */
    final public function at(float $difference, bool $normalized = false): float
    {
        if ($normalized) {
            return $this->NormalizationFactor * $this->atTransformed($this->TransformationFactor * $difference);
        }

        return $this->atTransformed($this->TransformationFactor * $difference);
    }

    abstract protected function atTransformed(float $u): float;

    /**
     * @param bool $normalized flag if values should be normalized
     * @return float[]
     */
    final public function valuesAt(array $differences, bool $normalized = false): array
    {
        $values = array_map(
            $this->atTransformed(...),
            array_map(fn ($v): float => $this->TransformationFactor * $v, $differences)
        );

        if ($normalized) {
            return array_map(fn (float $v): float => $this->NormalizationFactor * $v, $values);
        }

        return $values;
    }
}
