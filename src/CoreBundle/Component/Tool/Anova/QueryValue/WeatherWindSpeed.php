<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceInDecimalFormatUnit;

class WeatherWindSpeed extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'windSpeed';
    }

    /**
     * @return AbstractPaceInDecimalFormatUnit
     */
    public function getValueUnit(UnitSystem $unitSystem)
    {
        return $unitSystem->getWindSpeedUnit();
    }
}
