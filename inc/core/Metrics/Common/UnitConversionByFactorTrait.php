<?php

namespace Runalyze\Metrics\Common;

trait UnitConversionByFactorTrait
{
    abstract public function getFactorFromBaseUnit(): float;

    /**
     * @param mixed $valueInThisUnit
     */
    final public function toBaseUnit($valueInThisUnit): float
    {
        return $valueInThisUnit / $this->getFactorFromBaseUnit();
    }

    /**
     * @param mixed $valueInBaseUnit
     */
    final public function fromBaseUnit($valueInBaseUnit): float
    {
        return $valueInBaseUnit * $this->getFactorFromBaseUnit();
    }
}
