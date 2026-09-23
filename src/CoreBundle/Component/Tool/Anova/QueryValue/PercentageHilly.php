<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\Factorial;

class PercentageHilly extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'percentageHilly';
    }

    public function getValueUnit(UnitSystem $unitSystem): Factorial
    {
        return new Factorial('%', 100, 0);
    }
}
