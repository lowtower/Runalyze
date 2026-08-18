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

    /**
     * @param UnitSystem $unitSystem
     * @return None
     */
    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Common\Unit\None
    {
        return new None();
    }
}
