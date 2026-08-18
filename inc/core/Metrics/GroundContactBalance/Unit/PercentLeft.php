<?php

namespace Runalyze\Metrics\GroundContactBalance\Unit;

use Runalyze\Metrics\Common\FormattableUnitInterface;
use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class PercentLeft extends AbstractGroundContactBalanceUnit implements FormattableUnitInterface
{
    use UnitConversionByFactorTrait;

    /**
     * @return int|float
     */
    public function getFactorFromBaseUnit(): float
    {
        return 0.01;
    }

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '%L';
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return 2;
    }
}
