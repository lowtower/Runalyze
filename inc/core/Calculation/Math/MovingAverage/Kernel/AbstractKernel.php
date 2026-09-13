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
    private float $TransformationFactor;

    protected float $Width;

    /**
     * @var float
     */
    protected $DefaultWidth = 2.0;

    /**
     * @var float
     */
    protected $NormalizationFactor = 1.0;

    /**
     * @param float $width
     * @throws \InvalidArgumentException
     */
    public function __construct($width)
    {
        if (! is_numeric($width) || $width <= 0.0) {
            throw new \InvalidArgumentException('Kernel width must be a positive float.');
        }

        $this->Width = (float) $width;
        $this->TransformationFactor = $this->DefaultWidth / $this->Width;
    }

    /**
     * @return float
     */
    final public function width()
    {
        return $this->Width;
    }

    /**
     * @param int|float $difference difference to X_0
     * @param bool $normalized flag if values should be normalized
     * @return float
     */
    final public function at($difference, $normalized = false)
    {
        if ($normalized) {
            return $this->NormalizationFactor * $this->atTransformed($this->TransformationFactor * $difference);
        }

        return $this->atTransformed($this->TransformationFactor * $difference);
    }

    /**
     * @param float $u
     * @return float
     */
    abstract protected function atTransformed($u);

    /**
     * @param bool $normalized flag if values should be normalized
     * @return float[]
     */
    final public function valuesAt(array $differences, $normalized = false)
    {
        $values = array_map(
            [$this, 'atTransformed'],
            array_map(fn ($v) => $this->TransformationFactor * $v, $differences)
        );

        if ($normalized) {
            return array_map(fn (float $v): float => $this->NormalizationFactor * $v, $values);
        }

        return $values;
    }
}
