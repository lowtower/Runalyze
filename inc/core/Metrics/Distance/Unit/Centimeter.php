<?php

namespace Runalyze\Metrics\Distance\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Centimeter extends AbstractDistanceUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'cm';
    }

    public function getFactorFromBaseUnit(): int
    {
        return 100000;
    }
}
