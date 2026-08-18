<?php

namespace Runalyze\Metrics\Time\Unit;

use Runalyze\Metrics\Common\FormattableUnitInterface;
use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Miliseconds extends AbstractTimeUnit implements FormattableUnitInterface
{
    use UnitConversionByFactorTrait;

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'ms';
    }

    /**
     * @return float
     */
    public function getFactorFromBaseUnit(): int
    {
        return 1000;
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return 0;
    }
}
