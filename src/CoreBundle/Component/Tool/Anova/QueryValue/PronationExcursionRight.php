<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\Simple;

class PronationExcursionRight extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'avgPronationExcursionRight';
    }

    public function getValueUnit(UnitSystem $unitSystem): Simple
    {
        return new Simple('°');
    }
}
