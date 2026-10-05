<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Common;

interface ComparableUnitInterface extends UnitInterface
{
    /**
     * @return mixed value in base unit
     */
    public function compareBaseUnit(mixed $baseValueInBaseUnit, mixed $comparisonValueInBaseUnit): mixed;
}
