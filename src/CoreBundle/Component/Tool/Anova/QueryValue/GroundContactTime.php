<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\Simple;

class GroundContactTime extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'groundcontact';
    }

    /**
     * @param UnitSystem $unitSystem
     * @return Simple
     */
    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Common\Unit\Simple
    {
        return new Simple('ms');
    }
}
