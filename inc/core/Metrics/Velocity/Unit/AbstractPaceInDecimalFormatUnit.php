<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Velocity\Unit;

use Runalyze\Metrics\Common\FormattableUnitInterface;
use Runalyze\Metrics\Common\UnitConversionByDividendTrait;

abstract class AbstractPaceInDecimalFormatUnit extends AbstractPaceUnit implements FormattableUnitInterface
{
    use UnitConversionByDividendTrait;

    public function getDecimals(): int
    {
        return 1;
    }
}
