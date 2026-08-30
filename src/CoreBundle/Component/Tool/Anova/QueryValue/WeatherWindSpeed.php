<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;

class WeatherWindSpeed extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'windSpeed';
    }

    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Velocity\Unit\AbstractPaceInDecimalFormatUnit
    {
        return $unitSystem->getWindSpeedUnit();
    }
}
