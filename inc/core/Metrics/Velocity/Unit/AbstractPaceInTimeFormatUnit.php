<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Velocity\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

abstract class AbstractPaceInTimeFormatUnit extends AbstractPaceUnit
{
    use UnitConversionByFactorTrait;
}
