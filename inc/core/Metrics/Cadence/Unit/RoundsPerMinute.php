<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Cadence\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;

class RoundsPerMinute extends AbstractCadenceUnit
{
    use BaseUnitTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'rpm';
    }
}
