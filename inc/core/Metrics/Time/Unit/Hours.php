<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Time\Unit;

use Runalyze\Metrics\Common\FormattableUnitInterface;
use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Hours extends AbstractTimeUnit implements FormattableUnitInterface
{
    use UnitConversionByFactorTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'h';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 1 / 3600;
    }

    public function getDecimals(): int
    {
        return 1;
    }
}
