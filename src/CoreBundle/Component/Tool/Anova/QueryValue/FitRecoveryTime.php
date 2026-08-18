<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\Factorial;

class FitRecoveryTime extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'fitRecoveryTime';
    }

    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Common\Unit\Factorial
    {
        return new Factorial('h', 1/60, 0);
    }
}
