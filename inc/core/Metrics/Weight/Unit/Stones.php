<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Weight\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Stones extends AbstractWeightUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'st';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 0.157473;
    }
}
