<?php

namespace Runalyze\Metrics\Weight\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Stones extends AbstractWeightUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'st';
    }

    /**
     * @return float
     */
    public function getFactorFromBaseUnit()
    {
        return 0.157473;
    }
}
