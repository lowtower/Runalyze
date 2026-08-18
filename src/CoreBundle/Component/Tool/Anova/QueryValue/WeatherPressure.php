<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Cadence\Unit\AbstractCadenceUnit;
use Runalyze\Metrics\Common\Unit\Simple;

class WeatherPressure extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'pressure';
    }

    /**
     * @return AbstractCadenceUnit
     */
    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Common\Unit\Simple
    {
        return new Simple('hpa');
    }
}
