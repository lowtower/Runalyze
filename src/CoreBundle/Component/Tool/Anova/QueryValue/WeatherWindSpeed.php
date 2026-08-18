<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Cadence\Unit\AbstractCadenceUnit;

class WeatherWindSpeed extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'windSpeed';
    }

    /**
     * @param UnitSystem $unitSystem
     * @return AbstractCadenceUnit
     */
    public function getValueUnit(UnitSystem $unitSystem)
    {
        return new None();
    }
}
