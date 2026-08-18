<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\HeartRate\Unit\AbstractHeartRateUnit;

class HeartRateMaximum extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'pulseMax';
    }

    /**
     * @return AbstractHeartRateUnit
     */
    public function getValueUnit(UnitSystem $unitSystem)
    {
        return $unitSystem->getHeartRateUnit();
    }
}
