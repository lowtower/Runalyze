<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\Simple;

class WeatherHumidity extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'humidity';
    }

    public function getValueUnit(UnitSystem $unitSystem): Simple
    {
        return new Simple('%');
    }
}
