<?php

namespace Runalyze\Metrics\Distance\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Meter extends AbstractDistanceUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'm';
    }

    public function getFactorFromBaseUnit(): int
    {
        return 1000;
    }
}
