<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Velocity\Unit;

use Runalyze\Metrics\Common\AbstractComparableUnit;

abstract class AbstractPaceUnit extends AbstractComparableUnit
{
    public function getUnit(): string
    {
        $appendix = $this->getAppendix();

        if (str_starts_with($appendix, '/')) {
            return 'min' . $appendix;
        }

        return $appendix;
    }
}
