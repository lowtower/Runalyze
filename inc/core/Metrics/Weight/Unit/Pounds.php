<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Weight\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Pounds extends AbstractWeightUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'lbs';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 2.204622;
    }
}
