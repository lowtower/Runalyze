<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\Simple;

class BrakingGsRight extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'avgBrakingGsRight';
    }

    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Common\Unit\Simple
    {
        return new Simple('G');
    }
}
