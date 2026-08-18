<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Distance\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Millimeter extends AbstractDistanceUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'mm';
    }

    public function getFactorFromBaseUnit(): int
    {
        return 1000000;
    }
}
