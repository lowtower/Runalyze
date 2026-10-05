<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Common;

abstract class AbstractComparableUnit implements ComparableUnitInterface
{
    /**
     * @return mixed value in base unit
     */
    public function compareBaseUnit(mixed $baseValueInBaseUnit, mixed $comparisonValueInBaseUnit): mixed
    {
        return $this->toBaseUnit(
            $this->fromBaseUnit($baseValueInBaseUnit) - $this->fromBaseUnit($comparisonValueInBaseUnit)
        );
    }
}
