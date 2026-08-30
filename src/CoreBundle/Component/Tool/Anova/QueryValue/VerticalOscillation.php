<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\Factorial;
use Runalyze\Metrics\Common\UnitInterface;

class VerticalOscillation extends AbstractOneColumnValue
{
    protected function getColumn(): string
    {
        return 'verticalOscillation';
    }

    public function getValueUnit(UnitSystem $unitSystem): UnitInterface
    {
        return new Factorial('cm', 0.1);
    }
}
