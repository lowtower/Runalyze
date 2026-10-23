<?php

declare(strict_types=1);

namespace Runalyze\Mathematics\Scale;

/**
 * Two-part percental scale
 *
 * This linear scale is made of two linear parts, split at the inflection point that will be mapped to 50%.
 */
class TwoPartPercental extends Percental
{
    protected float $InflectionPoint;

    public function __construct(float $min = 0.0, float $inflectionPoint = 50.0, float $max = 100.0)
    {
        parent::__construct($min, $max);

        $this->setInflectionPoint($inflectionPoint);
    }

    public function setInflectionPoint(float $inflectionPoint): void
    {
        $this->InflectionPoint = $inflectionPoint;
    }

    public function transform(float $input): float
    {
        if ($input < $this->InflectionPoint) {
            $value = ($input - $this->Min) * 50.0 / ($this->InflectionPoint - $this->Min);
        } else {
            $value = 50.0 + ($input - $this->InflectionPoint) * 50.0 / ($this->Max - $this->InflectionPoint);
        }

        return min(100.0, max(0.0, $value));
    }
}
