<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Cadence\Unit\AbstractCadenceUnit;

class Cadence extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'cadence';
    }

    public function getValueUnit(UnitSystem $unitSystem): AbstractCadenceUnit
    {
        return $unitSystem->getCadenceUnit();
    }
}
