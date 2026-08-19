<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Temperature\Unit\AbstractTemperatureUnit;

class WeatherTemperature extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'temperature';
    }

    public function getValueUnit(UnitSystem $unitSystem): AbstractTemperatureUnit
    {
        return $unitSystem->getTemperatureUnit();
    }
}
