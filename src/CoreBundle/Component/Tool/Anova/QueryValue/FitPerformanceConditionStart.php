<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\Linear;

class FitPerformanceConditionStart extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'fitPerformanceCondition';
    }

    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Common\Unit\Linear
    {
        return new Linear(
            function ($value) { return $value - 100; },
            function ($value) { return $value + 100; }
        );
    }
}
