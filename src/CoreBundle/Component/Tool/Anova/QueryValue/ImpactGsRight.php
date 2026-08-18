<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\Simple;

class ImpactGsRight extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'avgImpactGsRight';
    }

    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Common\Unit\Simple
    {
        return new Simple('G');
    }
}
