<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Time\Unit;

use Runalyze\Metrics\Common\FormattableUnitInterface;
use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Miliseconds extends AbstractTimeUnit implements FormattableUnitInterface
{
    use UnitConversionByFactorTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'ms';
    }

    public function getFactorFromBaseUnit(): int
    {
        return 1000;
    }

    public function getDecimals(): int
    {
        return 0;
    }
}
