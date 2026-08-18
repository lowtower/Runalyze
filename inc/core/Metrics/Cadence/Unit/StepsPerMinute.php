<?php

namespace Runalyze\Metrics\Cadence\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class StepsPerMinute extends AbstractCadenceUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'spm';
    }

    /**
     * @return int
     */
    public function getFactorFromBaseUnit(): int
    {
        return 2;
    }
}
