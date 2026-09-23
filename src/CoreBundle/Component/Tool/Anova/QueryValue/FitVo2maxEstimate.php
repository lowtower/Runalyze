<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\None;

class FitVo2maxEstimate extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'fitVO2maxEstimate';
    }

    public function getValueUnit(UnitSystem $unitSystem): None
    {
        return new None();
    }
}
