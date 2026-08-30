<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\GroundContactBalance\Unit\AbstractGroundContactBalanceUnit;
use Runalyze\Metrics\GroundContactBalance\Unit\PercentLeft;

class GroundContactBalance extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'groundcontactBalance';
    }

    public function getValueUnit(UnitSystem $unitSystem): AbstractGroundContactBalanceUnit
    {
        return new PercentLeft();
    }
}
