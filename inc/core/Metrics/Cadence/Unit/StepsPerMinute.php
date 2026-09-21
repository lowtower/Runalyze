<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Cadence\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class StepsPerMinute extends AbstractCadenceUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'spm';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 2.0;
    }
}
