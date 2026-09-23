<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit;

class Distance extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'distance';
    }

    public function getValueUnit(UnitSystem $unitSystem): AbstractDistanceUnit
    {
        return $unitSystem->getDistanceUnit();
    }
}
