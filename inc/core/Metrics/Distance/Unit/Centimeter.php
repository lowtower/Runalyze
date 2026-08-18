<?php

namespace Runalyze\Metrics\Distance\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Centimeter extends AbstractDistanceUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'cm';
    }

    /**
     * @return int
     */
    public function getFactorFromBaseUnit(): int
    {
        return 100000;
    }
}
