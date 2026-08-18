<?php

namespace Runalyze\Metrics\Distance\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Feet extends AbstractDistanceUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'ft';
    }

    /**
     * @return float
     */
    public function getFactorFromBaseUnit(): float
    {
        return 3280.84;
    }
}
