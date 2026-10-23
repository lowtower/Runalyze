<?php

declare(strict_types=1);

namespace Runalyze\Mathematics\Scale;

/**
 * Percental scale
 *
 * This linear scale will map a given minimum to 0 and a given maximum to 100.
 */
class Percental implements ScaleInterface
{
    protected float $Min;

    protected float $Max;

    public function __construct(float $min = 0.0, float $max = 100.0)
    {
        $this->setMinimum($min);
        $this->setMaximum($max);
    }

    public function setMinimum(float $min): void
    {
        $this->Min = $min;
    }

    public function setMaximum(float $max): void
    {
        $this->Max = $max;
    }

    public function transform(float $input): float
    {
        if ($this->Max === $this->Min) {
            return 0.0;
        }

        return min(100.0, 100.0 * max(0.0, $input - $this->Min) / ($this->Max - $this->Min));
    }
}
