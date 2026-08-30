<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;

class HeartRateAverage extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'pulseAvg';
    }

    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\HeartRate\Unit\AbstractHeartRateUnit
    {
        return $unitSystem->getHeartRateUnit();
    }
}
