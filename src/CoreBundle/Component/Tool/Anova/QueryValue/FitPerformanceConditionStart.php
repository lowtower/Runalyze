<?php

declare(strict_types=1);

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
            fn ($value) => $value - 100,
            fn ($value) => $value + 100
        );
    }
}
