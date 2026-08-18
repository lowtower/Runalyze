<?php

namespace Runalyze\Metrics\Weight\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Pounds extends AbstractWeightUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'lbs';
    }

    /**
     * @return float
     */
    public function getFactorFromBaseUnit()
    {
        return 2.204622;
    }
}
