<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Energy\Unit;

use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Kilojoules extends AbstractEnergyUnit
{
    use UnitConversionByFactorTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'kJ';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 4.1868;
    }
}
