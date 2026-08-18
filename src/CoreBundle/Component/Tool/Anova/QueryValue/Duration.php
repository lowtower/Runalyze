<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Time\Unit\AbstractTimeUnit;
use Runalyze\Metrics\Time\Unit\Hours;

class Duration extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 's';
    }

    /**
     * @return AbstractTimeUnit
     */
    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Time\Unit\Hours
    {
        return new Hours();
    }
}
