<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Distance\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Miles extends AbstractDistanceUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'mi';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 0.621371192;
    }
}
