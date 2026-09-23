<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\HeartRate\Unit\AbstractHeartRateUnit;

class HeartRateAverage extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'pulseAvg';
    }

    public function getValueUnit(UnitSystem $unitSystem): AbstractHeartRateUnit
    {
        return $unitSystem->getHeartRateUnit();
    }
}
