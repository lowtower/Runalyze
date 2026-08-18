<?php

namespace Runalyze\Metrics\Distance\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Feet extends AbstractDistanceUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'ft';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 3280.84;
    }
}
