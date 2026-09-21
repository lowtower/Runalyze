<?php

namespace Runalyze\Metrics\Common;

trait UnitConversionByDividendTrait
{
    /**
     * @return int|float
     */
    abstract public function getDividendFromBaseUnit();

    /**
     * @param mixed $valueInThisUnit
     */
    final public function toBaseUnit($valueInThisUnit): float
    {
        return $this->getDividendFromBaseUnit() / $valueInThisUnit;
    }

    /**
     * @param mixed $valueInBaseUnit
     */
    final public function fromBaseUnit($valueInBaseUnit): float
    {
        if ($valueInBaseUnit == 0) {
            return 0.0;
        }

        return $this->getDividendFromBaseUnit() / $valueInBaseUnit;
    }
}
