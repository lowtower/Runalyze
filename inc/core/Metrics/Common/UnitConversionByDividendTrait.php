<?php

namespace Runalyze\Metrics\Common;

trait UnitConversionByDividendTrait
{
    abstract public function getDividendFromBaseUnit(): int|float;

    final public function toBaseUnit(mixed $valueInThisUnit): float
    {
        return $this->getDividendFromBaseUnit() / $valueInThisUnit;
    }

    final public function fromBaseUnit(mixed $valueInBaseUnit): float
    {
        if ($valueInBaseUnit == 0) {
            return 0.0;
        }

        return $this->getDividendFromBaseUnit() / $valueInBaseUnit;
    }
}
