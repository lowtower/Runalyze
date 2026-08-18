<?php

namespace Runalyze\Metrics\Time\Unit;

use Runalyze\Metrics\Common\FormattableUnitInterface;
use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Hours extends AbstractTimeUnit implements FormattableUnitInterface
{
    use UnitConversionByFactorTrait;

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'h';
    }

    /**
     * @return float
     */
    public function getFactorFromBaseUnit(): float
    {
        return 1/3600;
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return 1;
    }
}
