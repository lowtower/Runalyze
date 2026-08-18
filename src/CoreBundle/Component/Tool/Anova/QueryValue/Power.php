<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Power\Unit\Watts;

class Power extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'power';
    }

    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Power\Unit\Watts
    {
        return new Watts();
    }
}
