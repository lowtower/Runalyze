<?php

namespace Runalyze\Metrics\GroundContactBalance\Unit;

use Runalyze\Metrics\Common\FormattableUnitInterface;
use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class PercentLeft extends AbstractGroundContactBalanceUnit implements FormattableUnitInterface
{
    use UnitConversionByFactorTrait;

    public function getFactorFromBaseUnit(): float
    {
        return 0.01;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '%L';
    }

    public function getDecimals(): int
    {
        return 2;
    }
}
