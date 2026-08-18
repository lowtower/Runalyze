<?php

namespace Runalyze\Metrics\Distance\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Yards extends AbstractDistanceUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'yd';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 1093.6133;
    }
}
