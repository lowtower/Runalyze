<?php

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

    /**
     * @return UnitInterface
     */
    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Common\Unit\Factorial
    {
        return new Factorial('cm', 0.1);
    }
}
