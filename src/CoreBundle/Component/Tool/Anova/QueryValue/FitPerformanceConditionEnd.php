<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\Linear;

class FitPerformanceConditionEnd extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'fitPerformanceConditionEnd';
    }

    public function getValueUnit(UnitSystem $unitSystem): Linear
    {
        return new Linear(
            fn ($value): int|float => $value - 100,
            fn ($value): int|float => $value + 100
        );
    }
}
