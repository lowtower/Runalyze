<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\None;

class Rpe extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'rpe';
    }

    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Common\Unit\None
    {
        return new None();
    }
}
